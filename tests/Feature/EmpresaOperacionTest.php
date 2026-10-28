<?php

use App\Contracts\DocumentoPdfRenderer;
use App\Enums\OperacionEmpresa;
use App\Jobs\GenerarDocumentoPdf;
use App\Models\Activity;
use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use App\Models\Solicitud;
use App\Services\EmpresaOperacionService;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->withoutVite());

test('la matriz operativa es global y no omite restricciones para super admin', function (string $estatus) {
    $empresa = activarEmpresaPrueba();
    $empresa->update(['estatus' => $estatus]);
    $service = app(EmpresaOperacionService::class);
    foreach (OperacionEmpresa::cases() as $operacion) {
        $esperado = match ($operacion) {
            OperacionEmpresa::AtenderCartera => true,
            OperacionEmpresa::Capturar => $estatus !== 'suspendida',
            OperacionEmpresa::ReportarCartera => $estatus !== 'borrador',
            default => $estatus === 'activa',
        };
        expect($service->permite($operacion))->toBe($esperado);
    }
})->with(['borrador', 'activa', 'suspendida']);

test('sin configuración se permite capturar y se exige activar para originar', function () {
    expect(app(EmpresaOperacionService::class)->compartir()['estatus'])->toBe('borrador');
    expect(app(EmpresaOperacionService::class)->permite(OperacionEmpresa::Capturar))->toBeTrue();
    expect(app(EmpresaOperacionService::class)->permite(OperacionEmpresa::Originar))->toBeFalse();
});

test('el backend bloquea cada avance operativo cuando la empresa no está activa', function (string $estatus, string $accion) {
    [$actor, $solicitud] = prepararSolicitudAprobable($this);
    EmpresaConfiguracion::first()->update(['estatus' => $estatus]);
    [$ruta, $datos] = $accion === 'dictaminar'
        ? ['solicitudes.dictaminar', solicitudDictamenDatos(3)]
        : ['solicitudes.aprobar', ['lock_version' => 3, 'motivo' => 'Intento bloqueado por el estado de la empresa.']];
    $this->postJson(route($ruta, $solicitud), $datos)->assertUnprocessable()->assertJsonValidationErrors('empresa');
    expect($solicitud->fresh()->lock_version)->toBe(3);
})->with(['borrador', 'suspendida'])->with(['dictaminar', 'aprobar']);

test('contratación y desembolso bloquean llamadas directas en cada estatus inactivo', function (string $estatus, string $accion) {
    if ($accion === 'contrato') {
        [$actor, $solicitud, , , , , $datos] = prepararSolicitudConContrato($this);
        $ruta = route('solicitudes.paquete.store', $solicitud);
    } elseif ($accion === 'desembolso') {
        [$actor, $solicitud, , , $datos] = prepararDesembolsoQa($this);
        $ruta = route('solicitudes.desembolso.store', $solicitud);
    } else {
        [$actor, $solicitud, $paquete] = prepararContratoParaFirma($this);
        if ($accion === 'formalizar') {
            $this->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), datosFirma())->assertSessionHasNoErrors();
            $ruta = route('solicitudes.firmas.review', [$solicitud, \App\Models\SolicitudFirma::sole()]);
            $datos = datosRevisionFirma();
        } else {
            $ruta = route('solicitudes.firmas.store', [$solicitud, $paquete]);
            $datos = datosFirma();
        }
    }
    EmpresaConfiguracion::first()->update(['estatus' => $estatus]);
    $this->postJson($ruta, $datos)->assertUnprocessable()->assertJsonValidationErrors('empresa');
    $this->assertDatabaseCount('creditos', 0);
    $this->assertDatabaseCount('solicitud_formalizaciones', $accion === 'desembolso' ? 1 : 0);
})->with(['borrador', 'suspendida'])->with(['contrato', 'recibir_firma', 'formalizar', 'desembolso']);

test('borrador permite capturar solicitudes pero bloquea enviarlas con error español y auditoría', function () {
    $actor = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $actor->current_sucursal_id]);
    $producto = solicitudProducto($actor->id);
    $this->actingAs($actor)->post(route('solicitudes.store'), solicitudDatos($cliente, $producto))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->postJson(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])
        ->assertUnprocessable()->assertJsonValidationErrors('empresa');
    expect(Solicitud::first()->estado->value)->toBe('borrador');
    $actividad = Activity::where('event', 'empresa.operacion_bloqueada')->sole();
    expect($actividad->causer_id)->toBe($actor->id)
        ->and($actividad->subject_id)->toBe($solicitud->id)
        ->and($actividad->team_id)->toBe($actor->current_team_id)
        ->and($actividad->sucursal_id)->toBe($solicitud->sucursal_id)
        ->and($actividad->properties['result'])->toBe('blocked');
    expect(json_encode($actividad->properties))->not->toContain('validation.', 'rfc', 'password');
});

test('suspendida bloquea altas web y API incluso para super admin', function (string $ruta) {
    activarEmpresaPrueba()->update(['estatus' => 'suspendida']);
    $actor = actingAsSuperAdmin();
    $catalogos = clienteFiscalCatalogs();
    $this->actingAs($actor)->postJson(route($ruta === 'api' ? 'api.clientes.store' : 'clientes.store'), clientePayload($catalogos))
        ->assertUnprocessable()->assertJsonValidationErrors('empresa');
    $this->assertDatabaseCount('clientes', 0);
})->with(['web', 'api']);

test('el contrato exige RFC y correo aunque la plantilla no los use', function (string $campo) {
    [$actor, $solicitud, , , , , $data] = prepararSolicitudConContrato($this);
    EmpresaConfiguracion::first()->update([$campo => null]);
    $this->postJson(route('solicitudes.paquete.store', $solicitud), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('empresa');
    $this->assertDatabaseCount('solicitud_paquetes', 0);
})->with(['rfc', 'email']);

test('suspender detiene el PDF encolado sin renderizar ni reconstruir el contexto del iniciador', function () {
    [$actor, $solicitud, , , , , $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $documento = \App\Models\DocumentoGenerado::sole();
    $team = $actor->current_team_id;
    $sucursal = $actor->current_sucursal_id;
    EmpresaConfiguracion::first()->update(['estatus' => 'suspendida']);
    $actor->forceFill(['current_team_id' => null, 'current_sucursal_id' => null])->save();
    $this->mock(DocumentoPdfRenderer::class)->shouldNotReceive('render');
    $job = new GenerarDocumentoPdf($documento->id);
    app()->call([$job, 'handle']);
    app()->call([$job, 'handle']);
    expect($documento->fresh()->error_codigo)->toBe('EmpresaNoOperativa');
    $actividad = Activity::where('event', 'empresa.operacion_bloqueada')->sole();
    expect($actividad->causer_id)->toBeNull()
        ->and($actividad->team_id)->toBe($team)
        ->and($actividad->sucursal_id)->toBe($sucursal)
        ->and($actividad->subject_type)->toBe((new Cliente)->getMorphClass())
        ->and((int) $actividad->subject_id)->toBe($documento->cliente_id)
        ->and($actividad->properties['related']['id'])->toBe($documento->id)
        ->and($documento->creado_por)->toBe($actor->id);
    Storage::disk('local')->assertMissing('documentos-generados/'.$documento->cliente_id.'/'.$documento->id.'.pdf');
});

test('el PDF queda admitido antes de liberar el bloqueo que permite suspender la empresa', function () {
    [$actor, $solicitud, , , , , $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $documento = \App\Models\DocumentoGenerado::sole();
    $observada = false;
    \Illuminate\Support\Facades\Event::listen(\Illuminate\Database\Events\TransactionCommitted::class,
        function () use (&$observada, $documento) {
            if ($observada) {
                return;
            }
            $observada = true;
            expect($documento->fresh()->estado)->toBe(\App\Enums\DocumentoGeneradoEstado::Procesando);
            EmpresaConfiguracion::first()->update(['estatus' => 'suspendida']);
        });
    $this->mock(DocumentoPdfRenderer::class)->shouldReceive('render')->once()->andReturn("%PDF-1.4\nDocumento admitido\n%%EOF");
    app()->call([new GenerarDocumentoPdf($documento->id), 'handle']);
    expect($observada)->toBeTrue()->and($documento->fresh()->estado)->toBe(\App\Enums\DocumentoGeneradoEstado::Generado);
});
