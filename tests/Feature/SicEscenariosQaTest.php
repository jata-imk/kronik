<?php

use App\Models\SicQuery;
use App\Services\SICs\SicEscenariosQa;
use App\Services\SICs\SicOperacionService;
use Illuminate\Support\Facades\Http;

test('escenarios QA conservan el cliente y envían el cuerpo oficial exacto sin exigir ciudad original', function (string $api, int $cantidad) {
    [$actor, $cliente, $data] = sicQaFixture();
    config(['sic.escenarios_qa_habilitados' => true]);
    $cliente->direcciones->first()->codigoPostal->update(['datos_adicionales' => []]);
    $data['api'] = $api;
    $original = $cliente->fresh()->toArray();
    $catalogo = app(SicEscenariosQa::class);
    expect(collect($catalogo->catalogo($api))->where('procedencia', 'cdc_sandbox'))->toHaveCount($cantidad);
    $expected = $catalogo->resolver($api, null);
    Http::fake(['*' => Http::response(['score' => ['valor' => 404]])]);
    $service = app(SicOperacionService::class);
    $query = $service->preparar($cliente, $data, $actor);
    $service->ejecutar($query->id);
    $service->ejecutar($query->id);
    Http::assertSentCount(1);
    Http::assertSent(fn ($r) => $r->data() === $expected['peticion']);
    expect($cliente->fresh()->toArray())->toBe($original)
        ->and($query->fresh()->escenario_clave)->toBe($expected['clave'])
        ->and($service->leer($query, 'escenario'))->toBe($expected);
    expect($service->preparar($cliente, $data, $actor)->id)->toBe($query->id);
    expect(fn () => $service->preparar($cliente, [...$data, 'escenario_qa' => 'interno_fallo'], $actor))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
})->with([['fico_score_v2', 49], ['fintech', 27], ['rc_fico_score', 8]]);

test('simulaciones internas no envían HTTP y distinguen todos los resultados', function (string $caso, string $estado) {
    [$actor, $cliente, $data] = sicQaFixture();
    config(['sic.escenarios_qa_habilitados' => true]);
    Http::preventStrayRequests();
    $service = app(SicOperacionService::class);
    $q = $service->preparar($cliente, [...$data, 'escenario_qa' => 'interno_'.$caso], $actor);
    $service->ejecutar($q->id);
    $service->ejecutar($q->id);
    expect($q->fresh()->estado_operacion)->toBe($estado);
    Http::assertNothingSent();
    if ($caso === 'persona_no_encontrada') {
        expect($service->leer($q, 'resultado')['categoria_error'])->toBe('persona_no_encontrada');
    }
})->with([
    ['con_score', 'completada'], ['sin_score', 'completada'], ['bloqueado', 'completada'],
    ['persona_no_encontrada', 'error'], ['validacion', 'error'], ['autenticacion', 'error'],
    ['permisos', 'error'], ['limite', 'error'], ['fallo', 'error'], ['incompleta', 'error'], ['incierta', 'incierta'],
]);

test('guardas y selección inválida rechazan sin intentos y con mensaje español', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    Http::fake();
    $service = app(SicOperacionService::class);
    expect(fn () => $service->preparar($cliente, [...$data, 'escenario_qa' => 'interno_con_score'], $actor))
        ->toThrow(\Illuminate\Validation\ValidationException::class, 'desactivados');
    config(['sic.escenarios_qa_habilitados' => true]);
    $this->actingAs($actor)->post(route('circulo-credito.store', $cliente), [...$data, 'escenario_qa' => 'cdc_fintech_07'])
        ->assertSessionHasErrors(['escenario_qa' => 'Selecciona un escenario disponible para este servicio SIC.']);
    expect(SicQuery::count())->toBe(0);
    $q = $service->preparar($cliente, $data, $actor);
    config(['sic.escenarios_qa_habilitados' => false]);
    $service->ejecutar($q->id);
    expect($q->fresh()->estado_operacion)->toBe('cancelada');
    Http::assertNothingSent();
});

test('aprobación QA exige autorización sintética y congela su procedencia sin admitir E2', function (string $caso, bool $admitido) {
    [$actor, $solicitud, $cliente] = prepararSolicitudAprobable($this, sic: 'requerido', prepararCliente: function ($cliente, $actor) use (&$data) {
        [, , $data] = sicQaFixture($cliente, $actor);
    });
    config(['sic.escenarios_qa_habilitados' => true]);
    $revision = $solicitud->revisiones()->latest('numero')->firstOrFail();
    $service = app(SicOperacionService::class);
    $q = $service->preparar($cliente, [...$data, 'escenario_qa' => 'interno_'.$caso, 'solicitud_revision_id' => $revision->id], $actor);
    $service->ejecutar($q->id);
    $requisito = app(\App\Services\SICs\SicRequisito::class);
    $reglas = ['sic_apis' => ['fico_score_v2'], 'sic_sin_score' => 'evaluacion_manual'];
    expect($requisito->evaluar($revision->id, $cliente, $reglas))->toBeNull();
    config(['sic.aprobacion_sintetica_habilitada' => true]);
    $e = $requisito->evaluar($revision->id, $cliente, $reglas);
    if ($admitido) {
        expect($e['uso'])->toBe('qa')->and($e['procedencia'])->toBe('simulacion_interna');
        expect($requisito->evaluar($revision->id, $cliente, $reglas, $e))->toBe($e);
        if ($caso === 'sin_score') {
            expect($requisito->evaluar($revision->id, $cliente, [...$reglas, 'sic_sin_score' => 'bloquear']))->toBeNull();
        }
        config(['sic.aprobacion_sintetica_habilitada' => false]);
        expect($requisito->evaluar($revision->id, $cliente, $reglas, $e))->toBeNull();
    } else {
        expect($e)->toBeNull();
    }
})->with([['con_score', true], ['sin_score', true], ['bloqueado', false], ['fallo', false]]);

test('activar escenarios conserva repetición idempotente del intento anterior', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    $service = app(SicOperacionService::class);
    $q = $service->preparar($cliente, $data, $actor);
    config(['sic.escenarios_qa_habilitados' => true]);
    expect($service->preparar($cliente, $data, $actor)->id)->toBe($q->id);
    expect($service->leer($q, 'escenario'))->toBeNull();
    expect(SicQuery::count())->toBe(1);
});

test('lectura histórica no atribuye procedencia CDC al historial desconocido', function () {
    [$actor, $cliente] = sicQaFixture();
    $q = historicalSicQuery($cliente);
    $this->actingAs($actor)->get(route('consultas-sic.show', $q))->assertOk();
    $audit = \App\Models\Activity::where('event', 'cliente.sic.evidence_viewed')->sole();
    expect($audit->properties['provider'])->toBe('desconocida');
});

test('selecciones independientes conservan contexto y auditoría sin cuerpos ni secretos', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    $otroActor = actingAsSuperAdmin();
    $otroActor->sucursales()->attach($cliente->sucursal_id);
    $otroActor->forceFill(['current_sucursal_id' => $cliente->sucursal_id])->save();
    $otroCliente = \App\Models\Cliente::factory()->create(['sucursal_id' => $cliente->sucursal_id]);
    $consentimiento = $cliente->consentimientosSic()->first()->replicate();
    $consentimiento->cliente_id = $otroCliente->id;
    $consentimiento->save();
    $otrosDatos = [...$data, 'consentimiento_id' => $consentimiento->id, 'idempotency_key' => (string) \Illuminate\Support\Str::uuid()];
    config(['sic.escenarios_qa_habilitados' => true]);
    $service = app(SicOperacionService::class);
    $a = $service->preparar($cliente, [...$data, 'escenario_qa' => 'interno_con_score'], $actor);
    $b = $service->preparar($otroCliente, [...$otrosDatos, 'escenario_qa' => 'interno_sin_score'], $otroActor);
    $service->ejecutar($b->id);
    $service->ejecutar($a->id);
    expect($service->leer($a, 'resultado')['estado'])->toBe('con_score')
        ->and($service->leer($b, 'resultado')['estado'])->toBe('sin_score');
    $event = \App\Models\Activity::where('event', 'sic.requested')->where('subject_id', $a->id)->sole();
    expect($event->causer_id)->toBe($actor->id)->and($event->team_id)->toBe($a->team_id)
        ->and($event->sucursal_id)->toBe($a->sucursal_id)
        ->and($event->properties['provider'])->toBe('simulacion_interna')
        ->and($event->toJson())->not->toContain('QA-FAKE', 'consentimiento.pdf', 'primerNombre', 'Modelo sintético');
});
