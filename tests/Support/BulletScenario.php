<?php

namespace Tests\Support;

use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\RegimenFiscal;
use App\Models\Solicitud;
use App\Models\SolicitudPaquete;
use App\Models\User;
use App\Services\Documentos\DocumentoPlantillaVersionService;
use App\Services\FechaEmpresa;
use App\Services\OriginacionPoliticaService;
use App\Services\ProductoVersionService;
use App\Services\SolicitudFirmaService;
use App\Services\SolicitudPaqueteService;
use App\Services\SolicitudService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Fixtures por servicios reales, sin injertar condiciones, tablas ni hashes. */
final class BulletScenario
{
    public static function producto(User $actor, string $politica, string $base = 'obligacion_vencida', bool $activar = true): ProductoVersion
    {
        self::aislado();
        $service = app(ProductoVersionService::class);
        $producto = $service->crear([
            'clave' => 'BUL-'.Str::random(10), 'nombre' => 'Bullet QA '.$politica,
            'version' => [
                'monto_minimo' => '1.00', 'monto_maximo' => '100000.00',
                'tasa_ordinaria_anual' => '24.00', 'tasa_moratoria_anual' => '36.00',
                'dias_gracia_mora' => 0, 'cat_aplica' => true,
                'politica_mora' => ['gracia' => 'efectiva', 'intereses' => 'ambos'],
                'fiscalidad' => ['uso' => 'prueba', 'referencia' => 'Fixture sintética Bullet aislada',
                    'ordinario' => ['tratamiento' => 'no_causa'], 'moratorio' => ['tratamiento' => 'no_causa']],
                'periodicidades' => [['periodicidad' => 'mensual', 'plazo_minimo' => 1, 'plazo_maximo' => 24, 'plazo_predeterminado' => 2]],
                'reglas' => ['metodos_amortizacion' => ['bullet'], 'politica_interes_bullet' => $politica,
                    'base_ordinaria' => 'saldo_insoluto', 'base_moratoria' => $base, 'convencion_interes' => 'dias_reales_360',
                    'permite_prepago_parcial' => true, 'permite_liquidacion_anticipada' => true,
                    'monto_minimo_prepago' => '100.00', 'aplicacion_prepago' => 'conservar_fecha'],
                'comisiones' => [],
            ],
        ], $actor->id);
        $version = $producto->versiones()->sole();
        if ($activar) {
            $version = $service->activar($version, app(FechaEmpresa::class)->hoy()->toDateString());
        }
        app(OriginacionPoliticaService::class)->crear($version, [
            'version_anterior' => 0, 'modalidad' => 'individual', 'sic' => 'manual_permitido', 'monto_maximo' => '50000.00',
            'vigencia_dias' => 15, 'documentos' => ['ine'], 'confirmacion' => true,
            'referencia_validacion' => 'Prueba aislada sin proveedores ni operación real.',
            'criterio_capacidad' => 'Revisar ingresos y egresos sintéticos para el escenario Bullet.',
        ], $actor);

        return $version;
    }

    public static function cliente(User $actor): Cliente
    {
        self::aislado();
        $cliente = Cliente::factory()->create(['primer_nombre' => 'Bullet', 'apellido_paterno' => 'Sintético',
            // Historic DST makes loss of the named clock zone observable across processes.
            'fecha_nacimiento' => '2002-07-01',
            'sucursal_id' => $actor->current_sucursal_id]);
        $regimen = RegimenFiscal::firstOrCreate(['clave' => '612'], ['descripcion' => 'Régimen sintético', 'fisica' => true,
            'moral' => false, 'fecha_inicio_vigencia' => '2020-01-01', 'fecha_fin_vigencia' => '2099-12-31']);
        $cliente->datosFiscales()->create(['tipo_persona' => 'fisica', 'regimen_fiscal_id' => $regimen->id,
            'curp' => 'GODE561231HDFRRN09', 'rfc' => 'GODE561231GR8', 'razon_social' => 'Persona sintética Bullet']);
        $path = 'bullet-fixtures/'.Str::uuid().'.txt';
        $disk = config('documentos.disk', 'local');
        Storage::disk($disk)->put($path, 'Identidad sintética de pruebas, sin valor documental.');
        $cliente->documentos()->create(['tipo' => 'ine', 'version' => 1, 'estado' => 'validado', 'es_actual' => true,
            'disk' => $disk, 'path' => $path, 'mime_type' => 'text/plain', 'nombre_original' => 'identidad.txt',
            'tamano_bytes' => 50, 'revisado_en' => now(), 'revisado_por' => $actor->id]);

        return $cliente;
    }

    public static function solicitud(User $actor, ProductoVersion $producto): Solicitud
    {
        $cliente = self::cliente($actor);

        return app(SolicitudService::class)->crear([
            'cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid(), 'producto_version_id' => $producto->id,
            'monto' => '12000.00', 'plazo' => 2, 'periodicidad' => 'mensual', 'metodo' => 'bullet',
            'destino' => 'Recorrido sintético Bullet.', 'fecha_estimada' => app(FechaEmpresa::class)->hoy()->toDateString(),
        ], $actor)->fresh();
    }

    public static function aprobar(Solicitud $solicitud, User $actor): Solicitud
    {
        $service = app(SolicitudService::class);
        $solicitud = $service->enviar($solicitud, $solicitud->lock_version, $actor);
        foreach (['evaluacion', 'pld'] as $tipo) {
            $solicitud = $service->dictaminar($solicitud, [
                'lock_version' => $solicitud->lock_version, 'tipo_dictamen' => $tipo,
                'resultado' => $tipo === 'pld' ? 'sin_observaciones' : 'favorable',
                ...($tipo === 'pld' ? ['nivel_riesgo' => 'bajo'] : []),
                'fundamento' => 'Revisión sintética del ciclo Bullet.', 'fuentes' => 'Expediente aislado de pruebas.',
                'metodologia' => 'Fixture Bullet versión 1',
            ], $actor);
        }

        return $service->aprobar($solicitud, $solicitud->lock_version, 'Aprobación sintética para comprobar el ciclo Bullet.', $actor);
    }

    public static function formalizar(Solicitud $solicitud, User $actor): SolicitudPaquete
    {
        $templates = app(DocumentoPlantillaVersionService::class);
        $template = $templates->create(['clave' => 'BUL-'.Str::random(10), 'nombre' => 'Contrato Bullet QA',
            'tipo' => 'contrato', 'contenido_html' => '<p>Contrato sintético de {{cliente.nombre_completo}}.</p>', 'presentacion' => []], $actor->id);
        $version = $templates->activate($template->versiones()->sole());
        $paquete = app(SolicitudPaqueteService::class)->preparar($solicitud, [
            'version_id' => $version->id, 'lock_version' => $solicitud->lock_version,
            'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true,
        ], $actor);
        // Pest's enclosing transaction delays afterCommit; execute the actual job.
        if ($paquete->documento->estado !== \App\Enums\DocumentoGeneradoEstado::Generado) {
            app()->call([new \App\Jobs\GenerarDocumentoPdf($paquete->documento->id), 'handle']);
        }
        $firmaService = app(SolicitudFirmaService::class);
        $firma = $firmaService->recibir($solicitud->fresh(), $paquete->fresh(), [
            'lock_version' => $solicitud->fresh()->lock_version, 'fecha_firma' => app(FechaEmpresa::class)->hoy()->toDateString(),
            'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true,
        ], UploadedFile::fake()->createWithContent('firma.pdf', "%PDF-1.4\nFirma sintética ".Str::uuid()."\n%%EOF"), $actor);
        $firmaService->revisar($solicitud->fresh(), $firma, ['lock_version' => $solicitud->fresh()->lock_version,
            'estado_firma' => 'aceptada', 'confirmacion_revision' => true, 'confirmacion_qa' => true], $actor);

        return $paquete->fresh();
    }

    public static function desembolso(Solicitud $solicitud, SolicitudPaquete $paquete): array
    {
        return ['lock_version' => $solicitud->fresh()->lock_version,
            'fecha_desembolso' => app(FechaEmpresa::class)->hoy()->toDateString(),
            'importe' => $paquete->snapshot['tabla']['escenario']['efectivo_entregado'],
            'referencia' => 'BUL-DES-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    }

    public static function habilitar(): void
    {
        self::aislado();
        foreach (['aprobaciones_habilitadas', 'paquetes_qa_habilitados', 'firmas_qa_habilitadas', 'desembolsos_qa_habilitados', 'pagos_qa_habilitados', 'anticipos_qa_habilitados'] as $bandera) {
            config(['originacion.'.$bandera => true]);
        }
    }

    public static function pdfSintetico(): void
    {
        self::aislado();
        app()->instance(\App\Contracts\DocumentoPdfRenderer::class, new class implements \App\Contracts\DocumentoPdfRenderer
        {
            public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null, array $options = []): string
            {
                return "%PDF-1.4\nPDF sintético aislado.\n%%EOF";
            }
        });
    }

    private static function aislado(): void
    {
        if (! app()->environment(['testing', 'e2e'])) {
            throw new \RuntimeException('El escenario Bullet solo puede prepararse en pruebas aisladas.');
        }
    }
}
