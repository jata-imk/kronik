<?php

use App\Enums\SolicitudEstado;
use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Models\SolicitudRevision;
use App\Models\Sucursal;
use App\Services\FechaEmpresa;
use App\Services\ProductoVersionService;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function solicitudProducto(int $userId): ProductoVersion
{
    $producto = app(ProductoVersionService::class)->crear([
        'clave' => 'SOL-'.Str::random(8), 'nombre' => 'Crédito de solicitud',
        'version' => [
            'monto_minimo' => '5000.00', 'monto_maximo' => '100000.00',
            'tasa_ordinaria_anual' => '36.00', 'tasa_moratoria_anual' => '72.00',
            'dias_gracia_mora' => 3, 'cat_aplica' => true,
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

function prepararSolicitudAprobable($test, string $modalidad = 'individual', string $sic = 'manual_permitido'): array
{
    \Illuminate\Support\Facades\Storage::fake('local');
    config(['originacion.aprobaciones_habilitadas' => true]);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $regimen = \App\Models\RegimenFiscal::create(['clave' => '612', 'descripcion' => 'Régimen de prueba', 'fisica' => true,
        'moral' => false, 'fecha_inicio_vigencia' => '2020-01-01', 'fecha_fin_vigencia' => '2099-12-31']);
    $cliente->datosFiscales()->create(['tipo_persona' => 'fisica', 'regimen_fiscal_id' => $regimen->id,
        'curp' => 'GODE561231HDFRRN09', 'rfc' => 'GODE561231GR8', 'razon_social' => 'Persona de prueba']);
    $version = solicitudProducto($user->id);
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

test('accesos del cliente precargan solicitud y respetan permiso y sucursal', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->get(route('clientes.show', $cliente))->assertInertia(fn (Assert $page) => $page->where('puedeCrearSolicitud', true));
    $this->get(route('clientes.edit', $cliente))->assertInertia(fn (Assert $page) => $page->where('puedeCrearSolicitud', true));
    $this->get(route('solicitudes.create', ['cliente_id' => $cliente->id]))->assertInertia(fn (Assert $page) => $page->where('clienteInicial.id', $cliente->id));
    $user->forceFill(['current_sucursal_id' => null])->save();
    $this->get(route('clientes.show', $cliente))->assertInertia(fn (Assert $page) => $page->where('puedeCrearSolicitud', false));
    $user->forceFill(['is_super_admin' => false, 'current_sucursal_id' => $cliente->sucursal_id])->save();
    $user->givePermissionTo('read clientes');
    $this->get(route('clientes.show', $cliente))->assertInertia(fn (Assert $page) => $page->where('puedeCrearSolicitud', false));
    $this->get(route('solicitudes.create', ['cliente_id' => $cliente->id]))->assertForbidden();
});

test('aprobación individual conserva evidencia política y vigencia sin duplicarse', function () {
    [$user, $solicitud] = prepararSolicitudAprobable($this);
    $requisitos = app(\App\Services\SolicitudRequisitosService::class)->evaluar($solicitud, $user);
    expect(collect($requisitos['requisitos'])->where('cumplido', false)->all())->toBe([]);
    $data = ['lock_version' => 3, 'motivo' => 'Aprobación fundamentada de prueba.'];
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Aprobada);
    $resolucion = $solicitud->resoluciones()->firstOrFail();
    expect($resolucion->evidencia['politica']['condiciones']['modalidad'])->toBe('individual')
        ->and($resolucion->evidencia['documentos'])->toHaveCount(1)
        ->and($resolucion->vigente_hasta->toDateString())->toBe(app(FechaEmpresa::class)->hoy()->addDays(14)->toDateString());
    $this->travel(16)->days();
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page->where('aprobacionVencida', true));
    $this->travelBack();
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.aprobar', $solicitud), [...$data, 'lock_version' => 4])->assertSessionHasErrors('aprobacion');
    $this->assertDatabaseCount('solicitud_resoluciones', 1);
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 4, 'monto' => '11000.00'])->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.resolver', $solicitud), ['lock_version' => 4, 'accion' => 'devolver', 'motivo' => 'Actualizar condiciones para nueva revisión.', 'responsable_id' => $user->id])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Devuelta);
    $this->assertDatabaseCount('solicitud_resoluciones', 2);
});

test('dual impide autoaprobación incluso superadmin y admite otro aprobador', function () {
    [$capturista, $solicitud] = prepararSolicitudAprobable($this, 'dual');
    $data = ['lock_version' => 3, 'motivo' => 'Resolución dual de prueba documentada.'];
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    $aprobador = actingAsSuperAdmin();
    $aprobador->sucursales()->attach($capturista->current_sucursal_id);
    $aprobador->update(['current_sucursal_id' => $capturista->current_sucursal_id]);
    $this->actingAs($aprobador)->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Aprobada);
});

test('bloqueos de habilitación SIC documentos y dictámenes no pueden saltarse por URL', function () {
    [$user, $solicitud, $cliente, $version, $doc] = prepararSolicitudAprobable($this, 'individual', 'requerido');
    $data = ['lock_version' => 3, 'motivo' => 'Intento de aprobación bloqueada.'];
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('SIC integrado');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
    $this->assertDatabaseCount('sic_queries', 0);
});

test('expediente cambiado exige renovar dictamen y política nueva exige reenviar', function () {
    [$user, $solicitud, $cliente, $version, $doc] = prepararSolicitudAprobable($this);
    $data = ['lock_version' => 3, 'motivo' => 'Intento de aprobación con expediente cambiado.'];
    config(['originacion.aprobaciones_habilitadas' => false]);
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    config(['originacion.aprobaciones_habilitadas' => true]);
    $doc->update(['vence_en' => app(FechaEmpresa::class)->hoy()->subDay()]);
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('El expediente cambió después del dictamen')->toContain('incluida su validación')->toContain('Evaluación debe revisar')->toContain('Cumplimiento debe revisar')->toContain('Documento requerido');
    app(\App\Services\OriginacionPoliticaService::class)->crear($version, [...politicaSolicitudDatos(), 'version_anterior' => 1], $user);
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('La política cambió');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('política requiere permisos confirmación datos explícitos y preserva historia', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $version = solicitudProducto($user->id);
    $this->actingAs($user)->post(route('originacion-politicas.store', $version), [])->assertSessionHasErrors(['modalidad', 'sic', 'vigencia_dias', 'referencia_validacion']);
    expect(session('errors')->first('modalidad'))->toBe('El campo modalidad de aprobación es obligatorio.');
    $this->post(route('originacion-politicas.store', $version), politicaSolicitudDatos())->assertSessionHasNoErrors();
    $politica = \App\Models\OriginacionPolitica::firstOrFail();
    expect(fn () => $politica->delete())->toThrow(ValidationException::class);
    $this->post(route('originacion-politicas.store', $version), politicaSolicitudDatos())->assertSessionHasErrors('politica');
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo('read productos-crediticios');
    $this->get(route('originacion-politicas.show', $version))->assertForbidden();
    $this->post(route('originacion-politicas.store', $version), [...politicaSolicitudDatos(), 'version_anterior' => 1])->assertForbidden();
});

test('aprobar exige permiso explícito y sucursal incluso cuando los requisitos están completos', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    [$user, $solicitud] = prepararSolicitudAprobable($this);
    $data = ['lock_version' => 3, 'motivo' => 'Comprobación de autorización de aprobación.'];
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read solicitudes', 'review solicitudes']);
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertForbidden();
    $user->forceFill(['is_super_admin' => true, 'current_sucursal_id' => null])->save();
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('sucursal');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('último dictamen prevalece y cambios de cliente o pérdida del archivo bloquean', function () {
    [$user, $solicitud, $cliente] = prepararSolicitudAprobable($this);
    $this->post(route('solicitudes.dictaminar', $solicitud), [...solicitudDictamenDatos(3), 'resultado' => 'desfavorable'])->assertSessionHasNoErrors();
    $data = ['lock_version' => 4, 'motivo' => 'Intento con último dictamen desfavorable.'];
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('Falta evaluación favorable');
    $cliente->update(['origen_recursos' => 'Cambió el origen declarado']);
    \Illuminate\Support\Facades\Storage::disk('local')->delete('prueba/ine.pdf');
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('Los datos evaluados del cliente cambiaron')->toContain('Documento requerido');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('cambiar identidad fiscal invalida revisión y no permite aprobar persona moral', function () {
    [$user, $solicitud, $cliente] = prepararSolicitudAprobable($this);
    $cliente->datosFiscales()->update(['tipo_persona' => 'moral']);
    $this->post(route('solicitudes.aprobar', $solicitud), ['lock_version' => 3, 'motivo' => 'Intento con cambio de identidad fiscal.'])->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('solo permite aprobar personas físicas')->toContain('Los datos evaluados del cliente cambiaron');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('dictámenes son inmutables cifrados y ligados a revisión sin aprobación implícita', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(0))->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0]);
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(1))->assertSessionHasNoErrors();
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(1))->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(2, 'pld'))->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::EnRevision);
    $dictamen = $solicitud->dictamenes()->firstOrFail();
    expect($dictamen->getRawOriginal('contenido'))->not->toContain('Dictamen reservado')
        ->and($dictamen->toArray())->not->toHaveKey('contenido');
    expect(fn () => $dictamen->delete())->toThrow(ValidationException::class);
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('dictamenes.evaluacion.0.revision_actual', true)->where('dictamenes.pld.0.resultado', 'bloqueada'));
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'devolver', 'lock_version' => 3, 'responsable_id' => $user->id, 'motivo' => 'Corregir datos antes de nueva revisión.'])->assertSessionHasNoErrors();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 4])->assertSessionHasNoErrors();
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('dictamenes.evaluacion.0.revision_actual', false)->where('dictamenes.pld.0.revision_actual', false));
    $this->assertDatabaseCount('solicitud_dictamenes', 2);
    $this->assertDatabaseCount('sic_queries', 0);
    expect(json_encode($solicitud->eventos()->get()->toArray()))->not->toContain('Dictamen reservado');
});

test('notas reservadas y bandejas requieren permisos específicos independientes', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0]);
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(1, 'pld'))->assertSessionHasNoErrors();
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read solicitudes', 'update solicitudes']);
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('dictamenes.pld', null)->where('dictamenes.evaluacion', null));
    $this->get(route('cumplimiento.index'))->assertForbidden();
    $this->get(route('evaluacion-solicitudes.index'))->assertForbidden();
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(2, 'pld'))->assertForbidden();
    $user->givePermissionTo('read cumplimiento');
    $this->get(route('cumplimiento.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('bandeja', 'Cumplimiento')->where('filters.estado', 'en_revision')->where('solicitudes.total', 1));
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('dictamenes.pld.0.contenido.fundamento', solicitudDictamenDatos(1)['fundamento'])->where('dictamenes.evaluacion', null));
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(2, 'pld'))->assertForbidden();
    $user->givePermissionTo('create cumplimiento');
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(2, 'pld'))->assertSessionHasNoErrors();
});

test('dictamen exige fundamento y riesgo determinado para concluir sin observaciones', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0]);
    $this->post(route('solicitudes.dictaminar', $solicitud), ['lock_version' => 1, 'tipo_dictamen' => 'evaluacion'])
        ->assertSessionHasErrors(['fundamento' => 'El campo fundamento del dictamen es obligatorio.']);
    $this->post(route('solicitudes.dictaminar', $solicitud), [...solicitudDictamenDatos(1, 'pld'), 'resultado' => 'sin_observaciones', 'nivel_riesgo' => 'sin_determinar'])
        ->assertSessionHasErrors(['nivel_riesgo' => 'Determina el nivel de riesgo antes de concluir sin observaciones.']);
    $this->post(route('solicitudes.dictaminar', $solicitud), [...solicitudDictamenDatos(1), 'nivel_riesgo' => 'alto'])->assertSessionHasErrors('nivel_riesgo');
    $this->assertDatabaseCount('solicitud_dictamenes', 0);
});

test('devolver corregir y reenviar conserva ambas revisiones y cifra el motivo', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasNoErrors();
    $original = SolicitudRevision::firstOrFail();
    $payload = ['accion' => 'devolver', 'lock_version' => 1, 'responsable_id' => $user->id, 'motivo' => 'Corregir el destino de los recursos.'];
    $this->post(route('solicitudes.resolver', $solicitud), $payload)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.resolver', $solicitud), $payload)->assertSessionHasErrors('solicitud');
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Devuelta);
    $decision = $solicitud->resoluciones()->firstOrFail();
    expect($decision->motivo)->toBe($payload['motivo'])
        ->and($decision->getRawOriginal('motivo'))->not->toContain($payload['motivo'])
        ->and($decision->toArray())->not->toHaveKey('motivo');
    expect(fn () => $decision->update(['motivo' => 'Alterado']))->toThrow(ValidationException::class);
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('resoluciones.0.motivo', $payload['motivo']));
    $this->get(route('solicitudes.edit', $solicitud))->assertOk();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 2, 'destino' => 'Nuevo destino documentado.'])->assertSessionHasNoErrors();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 3])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::EnRevision)
        ->and($original->fresh()->snapshot['condiciones']['destino'])->toBe('Compra de herramienta.')
        ->and($solicitud->revisiones()->where('numero', 2)->firstOrFail()->snapshot['condiciones']['destino'])->toBe('Nuevo destino documentado.');
    $this->assertDatabaseCount('solicitud_resoluciones', 1);
    $this->assertDatabaseCount('producto_version_usos', 2);
    expect(json_encode($solicitud->eventos()->get()->toArray()))->not->toContain($payload['motivo']);
});

test('cancelación es terminal y sale de pendientes sin borrar evidencia', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'cancelar', 'lock_version' => 0, 'motivo' => 'El cliente desistió de la solicitud.'])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Cancelada);
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 1, 'destino' => 'Otro'])->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 1])->assertSessionHasErrors('solicitud');
    $this->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 1, 'responsable_id' => $user->id])->assertSessionHasErrors('solicitud');
    $this->get(route('solicitudes.trabajo'))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 0));
    $this->get(route('solicitudes.trabajo', ['estado' => 'cancelada']))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 1));
    $this->assertDatabaseCount('solicitudes', 1);
});

test('rechazo solo procede en revisión y exige permiso separado de captura', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $data = ['accion' => 'rechazar', 'lock_version' => 0, 'motivo' => 'No cumple condiciones de originación.'];
    $this->post(route('solicitudes.resolver', $solicitud), $data)->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasNoErrors();
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read solicitudes', 'update solicitudes']);
    $data['lock_version'] = 1;
    $this->post(route('solicitudes.resolver', $solicitud), $data)->assertForbidden();
    $user->givePermissionTo('review solicitudes');
    $this->post(route('solicitudes.resolver', $solicitud), $data)->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Rechazada);
    $this->post(route('solicitudes.resolver', $solicitud), [...$data, 'accion' => 'cancelar', 'lock_version' => 2])->assertForbidden();
});

test('resolución valida motivo responsable y sucursal en español', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0]);
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'devolver', 'lock_version' => 1])
        ->assertSessionHasErrors(['motivo' => 'El campo motivo operativo es obligatorio.', 'responsable_id']);
    $data = ['accion' => 'devolver', 'lock_version' => 1, 'motivo' => 'Completar información de destino.', 'responsable_id' => $user->id];
    $recipient = \App\Models\User::factory()->create(['status' => 'inactive']);
    $this->post(route('solicitudes.resolver', $solicitud), [...$data, 'responsable_id' => $recipient->id])->assertSessionHasErrors('responsable_id');
    $user->update(['current_sucursal_id' => null]);
    $this->post(route('solicitudes.resolver', $solicitud), $data)->assertSessionHasErrors('sucursal');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('crea un borrador mínimo reanudable sin aprobar ni consultar SIC', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $data = ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()];
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
    $solicitud = Solicitud::firstOrFail();
    expect($solicitud->estado)->toBe(SolicitudEstado::Borrador)
        ->and($solicitud->responsable_id)->toBe($user->id);
    $this->get(route('solicitudes.edit', $solicitud))->assertOk();
    $this->get(route('solicitudes.show', $solicitud))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Solicitudes/Show')->where('revision', null)->has('solicitud.eventos', 1));
    $this->get(route('solicitudes.trabajo'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 1));
    $this->assertDatabaseCount('sic_queries', 0);
    $this->assertDatabaseCount('solicitud_revisiones', 0);
});

test('crear es idempotente y no reutiliza clave con otro payload', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $data = ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()];
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.store'), [...$data, 'destino' => 'Otro'])->assertSessionHasErrors('clave_creacion');
    $this->assertDatabaseCount('solicitudes', 1);
    $this->assertDatabaseCount('solicitud_eventos', 1);
});

test('guardar rechaza edición concurrente y conserva cliente y sucursal originales', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $solicitud = Solicitud::firstOrFail();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'Equipo'])
        ->assertSessionHasNoErrors();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'Sobrescribir'])
        ->assertSessionHasErrors(['solicitud' => 'La solicitud cambió. Actualiza la página antes de continuar.']);
    expect($solicitud->fresh()->destino)->toBe('Equipo');
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 1, 'cliente_id' => $cliente->id])
        ->assertSessionHasErrors('cliente_id');
    $otra = Sucursal::create(['nombre' => 'Otra', 'clave' => 'OTRA', 'activa' => true]);
    $cliente->update(['sucursal_id' => $otra->id]);
    expect($solicitud->fresh()->sucursal_id)->toBe($user->current_sucursal_id);
});

test('enviar valida producto y congela revisión y uso sin duplicación', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, $version))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::EnRevision);
    $revision = SolicitudRevision::firstOrFail();
    expect($revision->snapshot['condiciones']['monto'])->toBe('10000.00')
        ->and($revision->snapshot['simulacion_informativa']['tabla'])->toHaveCount(13);
    $this->assertDatabaseHas('producto_version_usos', ['usable_type' => 'solicitud_revisiones', 'usable_id' => $revision->id]);
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasErrors('solicitud');
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 1, 'monto' => '20000'])
        ->assertSessionHasErrors('solicitud');
    $this->assertDatabaseCount('solicitud_revisiones', 1);
    expect(fn () => $revision->update(['snapshot_hash' => 'alterada']))->toThrow(ValidationException::class);
    $this->get(route('solicitudes.show', $solicitud))->assertOk();
});

test('producto retirado datos incompletos y condiciones fuera de rango impiden envío', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $data = solicitudDatos($cliente, $version);
    $data['monto'] = '1.00';
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasErrors();
    app(ProductoVersionService::class)->retirar($version);
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasErrors('producto_version_id');
    $this->assertDatabaseCount('solicitud_revisiones', 0);
    $this->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $this->post(route('solicitudes.enviar', Solicitud::latest('id')->first()), ['lock_version' => 0])
        ->assertSessionHasErrors('producto_version_id');
});

test('permisos de solicitudes y sucursal se verifican también en servidor', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $data = ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()];
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo('read clientes');
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertForbidden();
    $this->get(route('solicitudes.index'))->assertForbidden();
    $this->get(route('solicitudes.clientes', ['buscar' => 'Ma']))->assertForbidden();
    $user->givePermissionTo(['read solicitudes', 'create solicitudes']);
    $this->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertForbidden();
    $this->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 0, 'responsable_id' => $user->id])->assertForbidden();
    $user->forceFill(['is_super_admin' => true, 'current_sucursal_id' => null])->save();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'Cambio'])->assertSessionHasErrors('sucursal');
});

test('asignación genera tarea visible al responsable y rechaza usuarios inactivos', function () {
    $actor = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $actor->current_sucursal_id]);
    $this->actingAs($actor)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $solicitud = Solicitud::firstOrFail();
    $recipient = actingAsSuperAdmin();
    $recipient->sucursales()->attach($actor->current_sucursal_id);
    $this->actingAs($actor)->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 0, 'responsable_id' => $recipient->id])->assertSessionHasNoErrors();
    $this->actingAs($recipient)->get(route('solicitudes.trabajo'))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 1));
    $this->actingAs($actor)->get(route('solicitudes.trabajo'))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 0));
    $recipient->update(['status' => 'inactive']);
    $this->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 1, 'responsable_id' => $recipient->id])->assertSessionHasErrors('responsable_id');
});

test('solicitud impide eliminar el expediente del cliente', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $this->delete(route('clientes.destroy', $cliente))->assertSessionHasErrors('cliente');
    $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
});

test('validaciones de captura se presentan en español', function () {
    $this->actingAs(actingAsSuperAdmin())->post(route('solicitudes.store'), ['monto' => '1.123'])
        ->assertSessionHasErrors(['monto' => 'Escribe un monto positivo con máximo dos decimales.'])
        ->assertSessionHasErrors('cliente_id');
    expect(session('errors')->first('cliente_id'))->not->toContain('validation.');
});

test('un borrador permite quitar un producto retirado para recuperarse', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, $version))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    app(ProductoVersionService::class)->retirar($version);
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'producto_version_id' => null])
        ->assertSessionHasNoErrors();
    expect($solicitud->fresh()->producto_version_id)->toBeNull();
});

test('la fecha estimada de hoy se interpreta como fecha empresarial no como instante UTC', function () {
    config(['app.timezone' => 'America/Mexico_City']);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $data = solicitudDatos($cliente, $version);
    $data['fecha_estimada'] = app(FechaEmpresa::class)->hoy()->toDateString();
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.enviar', Solicitud::firstOrFail()), ['lock_version' => 0])->assertSessionHasNoErrors();
    expect(SolicitudRevision::firstOrFail()->snapshot['simulacion_informativa']['escenario']['fecha_disposicion'])
        ->toBe($data['fecha_estimada']);
});
