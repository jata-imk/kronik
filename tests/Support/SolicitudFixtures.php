<?php

use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Services\FechaEmpresa;
use App\Services\ProductoVersionService;
use Illuminate\Support\Str;

function prepararDesembolsoQa($test, bool $pagos = false): array
{
    [$user, $solicitud, $paquete] = prepararContratoParaFirma($test, $pagos);
    $test->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), datosFirma())->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    $test->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma())->assertSessionHasNoErrors();
    $test->travel(7)->days();
    config(['originacion.desembolsos_qa_habilitados' => true]);
    $data = ['fecha_desembolso' => app(FechaEmpresa::class)->hoy()->toDateString(), 'importe' => $paquete->snapshot['tabla']['escenario']['efectivo_entregado'],
        'referencia' => 'QA-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'lock_version' => 7, 'confirmacion_qa' => true];

    return [$user, $solicitud->fresh(), $paquete, $firma, $data];
}

function prepararContratoParaFirma($test, bool $pagos = false): array
{
    [$user, $solicitud, , , , , $data] = prepararSolicitudConContrato($test, pagos: $pagos);
    config(['originacion.firmas_qa_habilitadas' => true, 'documentos.disk' => 'local']);
    $test->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $paquete = \App\Models\SolicitudPaquete::firstOrFail();
    $original = "%PDF-1.4\nOriginal QA\n%%EOF";
    \Illuminate\Support\Facades\Storage::disk('local')->put('qa/original.pdf', $original);
    $paquete->documento->update(['estado' => 'generado', 'disk' => 'local', 'path' => 'qa/original.pdf',
        'archivo_hash' => hash('sha256', $original), 'generado_en' => now(), 'nombre_archivo' => 'original.pdf']);

    return [$user, $solicitud->fresh(), $paquete->fresh()];
}

function datosFirma(): array
{
    return ['archivo' => \Illuminate\Http\UploadedFile::fake()->createWithContent('firmado.pdf', "%PDF-1.4\nCopia firmada QA\n%%EOF"),
        'fecha_firma' => app(FechaEmpresa::class)->hoy()->toDateString(), 'confirmacion_qa' => true,
        'idempotency_key' => (string) Str::uuid(), 'lock_version' => 5];
}

function datosRevisionFirma(string $estado = 'aceptada', int $version = 6): array
{
    return ['estado_firma' => $estado, 'lock_version' => $version, 'confirmacion_revision' => true, 'confirmacion_qa' => true,
        'motivo' => $estado === 'rechazada' ? 'Falta la firma del cliente en el anexo.' : null];
}

function prepararSolicitudConContrato($test, bool $fiscalidad = true, bool $pagos = false): array
{
    \Illuminate\Support\Facades\Queue::fake();
    [$user, $solicitud, $cliente, $producto, $doc] = prepararSolicitudAprobable($test, fiscalidad: $fiscalidad, pagos: $pagos);
    config(['originacion.paquetes_qa_habilitados' => true]);
    $test->post(route('solicitudes.aprobar', $solicitud), ['lock_version' => 3, 'motivo' => 'Aprobación para probar paquete contractual.'])->assertSessionHasNoErrors();
    $service = app(\App\Services\Documentos\DocumentoPlantillaVersionService::class);
    $plantilla = $service->create(['clave' => 'contrato-qa', 'nombre' => 'Contrato de prueba', 'tipo' => 'contrato',
        'contenido_html' => '<p>Contrato para {{cliente.nombre_completo}}.</p>', 'presentacion' => []], $user->id);
    $version = $service->activate($plantilla->versiones->first());
    $data = ['version_id' => $version->id, 'lock_version' => 4, 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];

    return [$user, $solicitud->fresh(), $cliente, $producto, $doc, $version, $data];
}

function solicitudProducto(int $userId, bool $fiscalidad = true, bool $pagos = false): ProductoVersion
{
    $producto = app(ProductoVersionService::class)->crear([
        'clave' => 'SOL-'.Str::random(8), 'nombre' => 'Crédito de solicitud',
        'version' => [
            'monto_minimo' => '5000.00', 'monto_maximo' => '100000.00',
            'tasa_ordinaria_anual' => '36.00', 'tasa_moratoria_anual' => '72.00',
            'dias_gracia_mora' => 3, 'cat_aplica' => true,
            'politica_mora' => $pagos ? ['gracia' => 'efectiva', 'intereses' => 'ambos'] : null,
            'fiscalidad' => $fiscalidad ? ['uso' => 'prueba', 'referencia' => 'Configuración sintética QA',
                'ordinario' => ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'],
                'moratorio' => ['tratamiento' => $pagos ? 'exento' : 'no_definido']] : null,
            'periodicidades' => [['periodicidad' => 'mensual', 'plazo_minimo' => 3, 'plazo_maximo' => 24, 'plazo_predeterminado' => 12]],
            'reglas' => ['metodos_amortizacion' => ['cuota_nivelada', 'capital_fijo'], 'permite_prepago_parcial' => true,
                'permite_liquidacion_anticipada' => true, 'monto_minimo_prepago' => '500.00', 'aplicacion_prepago' => 'reducir_plazo'],
            'comisiones' => [],
        ],
    ], $userId);

    return app(ProductoVersionService::class)->activar($producto->versiones()->first(), app(FechaEmpresa::class)->hoy()->toDateString());
}

function solicitudDatos(Cliente $cliente, ProductoVersion $producto): array
{
    return [
        'cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid(),
        'producto_version_id' => $producto->id, 'monto' => '10000.00',
        'periodicidad' => 'mensual', 'plazo' => 12, 'metodo' => 'cuota_nivelada',
        'destino' => 'Compra de herramienta.', 'fecha_estimada' => app(FechaEmpresa::class)->hoy()->addWeek()->toDateString(),
    ];
}

function solicitudDictamenDatos(int $lockVersion, string $tipo = 'evaluacion'): array
{
    return [
        'lock_version' => $lockVersion, 'tipo_dictamen' => $tipo,
        'resultado' => $tipo === 'pld' ? 'bloqueada' : 'favorable',
        'fundamento' => 'Dictamen reservado sustentado en las evidencias revisadas.',
        'fuentes' => 'Expediente y evidencia documental identificada por el revisor.',
        'metodologia' => 'Manual interno de pruebas versión 1',
        ...($tipo === 'pld' ? ['nivel_riesgo' => 'alto'] : []),
    ];
}

function politicaSolicitudDatos(string $modalidad = 'individual', string $sic = 'manual_permitido'): array
{
    return ['version_anterior' => 0, 'modalidad' => $modalidad, 'sic' => $sic, 'monto_maximo' => '50000.00',
        'vigencia_dias' => 15, 'documentos' => ['ine'], 'referencia_validacion' => 'Validación interna de pruebas, no para producción.',
        'criterio_capacidad' => 'Revisar ingresos y egresos y fundamentar capacidad manualmente.', 'confirmacion' => true];
}

function prepararSolicitudAprobable($test, string $modalidad = 'individual', string $sic = 'manual_permitido', bool $fiscalidad = true, bool $pagos = false): array
{
    \Illuminate\Support\Facades\Storage::fake('local');
    config(['originacion.aprobaciones_habilitadas' => true]);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $regimen = \App\Models\RegimenFiscal::create(['clave' => '612', 'descripcion' => 'Régimen de prueba', 'fisica' => true,
        'moral' => false, 'fecha_inicio_vigencia' => '2020-01-01', 'fecha_fin_vigencia' => '2099-12-31']);
    $cliente->datosFiscales()->create(['tipo_persona' => 'fisica', 'regimen_fiscal_id' => $regimen->id,
        'curp' => 'GODE561231HDFRRN09', 'rfc' => 'GODE561231GR8', 'razon_social' => 'Persona de prueba']);
    $version = solicitudProducto($user->id, $fiscalidad, $pagos);
    app(\App\Services\OriginacionPoliticaService::class)->crear($version, politicaSolicitudDatos($modalidad, $sic), $user);
    $doc = $cliente->documentos()->create(['tipo' => 'ine', 'version' => 1, 'estado' => 'validado', 'es_actual' => true,
        'disk' => 'local', 'path' => 'prueba/ine.pdf', 'mime_type' => 'application/pdf', 'nombre_original' => 'ine.pdf',
        'tamano_bytes' => 10, 'revisado_en' => now(), 'revisado_por' => $user->id]);
    \Illuminate\Support\Facades\Storage::disk('local')->put('prueba/ine.pdf', 'fixture');
    $test->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, $version))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $test->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasNoErrors();
    $test->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(1))->assertSessionHasNoErrors();
    $test->post(route('solicitudes.dictaminar', $solicitud), [...solicitudDictamenDatos(2, 'pld'), 'resultado' => 'sin_observaciones', 'nivel_riesgo' => 'bajo'])->assertSessionHasNoErrors();

    return [$user, $solicitud->fresh(), $cliente, $version, $doc];
}
