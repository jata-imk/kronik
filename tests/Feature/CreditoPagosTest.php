<?php

use App\Models\CreditoPago;
use App\Services\Credito\PagoService;
use App\Services\FechaEmpresa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->withoutVite());

test('cobranza y reverso permanecen disponibles sin empresa activa', function (string $estatus) {
    [$actor, $credito, $data] = creditoPagableQa($this);
    \App\Models\EmpresaConfiguracion::first()->update(['estatus' => $estatus]);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $pago = CreditoPago::sole();
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), ['motivo' => 'Corrección sintética de cobranza suspendida.',
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true])->assertSessionHasNoErrors();
    $this->get(route('creditos.pagos.show', [$credito, $pago]))->assertOk();
    $this->assertDatabaseCount('credito_pagos', 2);
})->with(['borrador', 'suspendida']);

test('pago QA integra distribución persistencia idempotencia cifrado y reverso inmutable', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $preview = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->assertJsonMissingPath('snapshot')->json();
    $data['previa_hash'] = $preview['previa_hash'];
    expect((float) $preview['despues']['capital_insoluto'])->toBeLessThan(10000);
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $pago = CreditoPago::firstOrFail();
    $this->post(route('creditos.pagos.store', $credito), $data)->assertRedirect(route('creditos.pagos.show', [$credito, $pago]));
    $this->assertDatabaseCount('credito_pagos', 1);
    $this->assertDatabaseHas('communication_events', ['event_key' => 'payment:'.$pago->id,
        'event_type' => 'payment_received', 'credito_id' => $credito->id, 'cliente_id' => $credito->cliente_id,
        'sucursal_id' => $credito->sucursal_id, 'cancelled_at' => null]);
    $this->assertDatabaseCount('communication_events', 1);
    $this->assertDatabaseHas('activity_log', ['event' => 'communication.event_prepared', 'subject_type' => \App\Models\CommunicationEvent::class,
        'subject_id' => \App\Models\CommunicationEvent::sole()->id, 'sucursal_id' => $credito->sucursal_id, 'team_id' => $credito->team_id]);
    expect(DB::table('credito_pagos')->value('referencia'))->not->toContain($data['referencia']);
    expect(fn () => $pago->delete())->toThrow(ValidationException::class);
    expect(fn () => $pago->update(['importe' => '1.00']))->toThrow(ValidationException::class);
    $this->get(route('creditos.pagos.show', [$credito, $pago]))->assertOk();
    $reverso = ['motivo' => 'Corrección sintética del recibo de prueba.', 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), $reverso)->assertSessionHasNoErrors();
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), $reverso)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('credito_pagos', 2);
    $this->assertDatabaseCount('communication_events', 1);
    expect(\App\Models\CommunicationEvent::where('event_key', 'payment:'.$pago->id)->firstOrFail()->cancellation_reason)->toBe('payment_reversed');
    $this->assertDatabaseHas('activity_log', ['event' => 'communication.event_cancelled', 'subject_type' => \App\Models\CommunicationEvent::class]);
    expect(\App\Support\Decimal::round((string) $credito->movimientos()->sum('importe')))->toBe('10000.00');
    expect(app(PagoService::class)->consultar($credito)['resumen']['capital_insoluto'])->toBe('10000.00');
});

test('recordatorio preventivo usa la lectura vigente tres días antes y se cancela tras el pago', function () {
    [, $credito, $data] = creditoPagableQa($this);
    $due = $data['fecha_pago'];
    $this->travelTo(\Carbon\CarbonImmutable::parse($due.' 12:00:00', 'America/Mexico_City')->subDays(3));
    $service = app(\App\Services\CommunicationEventService::class);
    expect($service->reconcileDueReminders())->toBe(0);
    app(\App\Services\Credito\ProyeccionCreditoService::class)->sincronizar($credito);
    expect($service->reconcileDueReminders())->toBe(1);
    expect($service->reconcileDueReminders())->toBe(0);
    $event = \App\Models\CommunicationEvent::sole();
    expect($event->event_type)->toBe('upcoming_due')
        ->and($event->due_date->toDateString())->toBe($due)
        ->and($event->cancelled_at)->toBeNull();
    app(\App\Services\Credito\ProyeccionCreditoService::class)->invalidar($credito);
    expect($service->reconcileDueReminders())->toBe(0)
        ->and($event->fresh()->cancelled_at)->toBeNull();
    $this->travelTo(\Carbon\CarbonImmutable::parse($due.' 12:00:00', 'America/Mexico_City'));
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    expect($event->fresh()->cancellation_reason)->toBe('due_not_current');
    $this->assertDatabaseCount('communication_events', 2);
});

test('candidato de vencimiento expira sin convertirse en cobranza', function () {
    [, $credito, $data] = creditoPagableQa($this);
    $due = $data['fecha_pago'];
    $this->travelTo(\Carbon\CarbonImmutable::parse($due.' 12:00:00', 'America/Mexico_City')->subDays(3));
    app(\App\Services\Credito\ProyeccionCreditoService::class)->sincronizar($credito);
    $service = app(\App\Services\CommunicationEventService::class);
    expect($service->reconcileDueReminders())->toBe(1);
    $event = \App\Models\CommunicationEvent::sole();
    $this->travelTo(\Carbon\CarbonImmutable::parse($due.' 12:00:00', 'America/Mexico_City')->addDay());
    expect($service->reconcileDueReminders())->toBe(0)
        ->and($event->fresh()->cancellation_reason)->toBe('due_not_current');
    $this->assertDatabaseCount('communication_events', 1);
});

test('fallo de auditoría de comunicación revierte pago y proyección en la misma transacción', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $antes = DB::table('credito_lecturas')->where('credito_id', $credito->id)->count();
    $activity = \Mockery::mock(\App\Services\ActivityLogService::class)->makePartial();
    $activity->shouldReceive('log')->andReturnUsing(function ($event) {
        if ($event === \App\Enums\ActivityEvent::CommunicationEventPrepared) {
            throw new \RuntimeException('Fallo sintético de auditoría');
        }
    });
    app()->instance(\App\Services\ActivityLogService::class, $activity);

    expect(fn () => app(\App\Services\Credito\PagoService::class)->registrar($credito, $data, $actor))
        ->toThrow(\RuntimeException::class, 'Fallo sintético de auditoría');
    $this->assertDatabaseCount('credito_pagos', 0);
    $this->assertDatabaseCount('communication_events', 0);
    expect(DB::table('credito_lecturas')->where('credito_id', $credito->id)->count())->toBe($antes);
});

test('pago QA rechaza entradas inválidas sin mutación', function (string $caso) {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->json('previa_hash');
    match ($caso) {
        'cerrado' => config(['originacion.pagos_qa_habilitados' => false]),
        'excedente' => $data['importe'] = '9999999.00',
        'cambio' => $data['importe'] = '400.00',
        'futuro' => $data['fecha_pago'] = app(FechaEmpresa::class)->hoy()->addDay()->toDateString(),
        'sucursal' => $actor->forceFill(['current_sucursal_id' => null])->save(),
    };
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasErrors('pago');
    $this->assertDatabaseCount('credito_pagos', 0);
    $this->assertDatabaseCount('credito_movimientos', 1);
})->with(['cerrado', 'excedente', 'cambio', 'futuro', 'sucursal']);

test('pago QA permite fecha pasada sin historia posterior y bloquea retroactividad posterior', function () {
    [, $credito, $data] = creditoPagableQa($this);
    $this->travel(3)->days();
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $pago = CreditoPago::firstOrFail();
    expect($pago->created_at->toDateString())->not->toBe($pago->fecha_efectiva->toDateString());
    $this->postJson(route('creditos.pagos.preview', $credito), [...$data, 'fecha_pago' => $pago->fecha_efectiva->subDay()->toDateString()])
        ->assertUnprocessable()->assertJsonValidationErrors('pago');
});

test('pago QA exige permisos independientes y devuelve validación legible', function () {
    [$actor, $credito] = creditoPagableQa($this);
    $this->seed(\Database\Seeders\ModulesAndPermissionsSeeder::class);
    $actor->forceFill(['is_super_admin' => false])->save();
    $actor->givePermissionTo(['read creditos', 'read solicitudes', 'read clientes']);
    $this->get(route('creditos.pagos.create', $credito))->assertForbidden();
    $this->postJson(route('creditos.pagos.preview', $credito), [])->assertForbidden();
    $actor->givePermissionTo('pay creditos');
    $this->get(route('creditos.pagos.create', $credito))->assertOk();
    $response = $this->postJson(route('creditos.pagos.preview', $credito), [])->assertUnprocessable()
        ->assertJsonValidationErrors(['fecha_pago', 'importe', 'referencia', 'idempotency_key']);
    foreach (collect($response->json('errors'))->flatten() as $error) {
        expect($error)->not->toContain('validation.');
    }
    expect($actor->can('reversePayment', $credito))->toBeFalse();
    $actor->givePermissionTo('reverse payments creditos');
    expect($actor->can('reversePayment', $credito))->toBeTrue();
});

test('pago QA bloquea evidencia alterada y no presenta saldo cero', function () {
    [, $credito, $data] = creditoPagableQa($this);
    DB::table('creditos')->where('id', $credito->id)->update(['condiciones_hash' => str_repeat('0', 64)]);
    $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertUnprocessable()->assertJsonValidationErrors('pago');
    $situacion = app(PagoService::class)->consultar($credito->fresh());
    expect($situacion['resumen'])->toBeNull()->and($situacion['error'])->toContain('evidencia');
    $this->assertDatabaseCount('credito_pagos', 0);
});

test('pago QA invalida previa concurrente admite otro pago del mismo día y reserva referencias', function () {
    [, $credito, $data] = creditoPagableQa($this);
    $data['importe'] = '100.00';
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $otro = [...$data, 'idempotency_key' => (string) Str::uuid(), 'referencia' => 'OTRO-'.Str::uuid()];
    $otro['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $otro)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $this->post(route('creditos.pagos.store', $credito), $otro)->assertSessionHasErrors('pago');
    $otro['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $otro)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credito), $otro)->assertSessionHasNoErrors();
    $duplicado = [...$data, 'idempotency_key' => (string) Str::uuid()];
    $duplicado['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $duplicado)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credito), $duplicado)->assertSessionHasErrors('pago');
    $this->assertDatabaseCount('credito_pagos', 2);
    $primero = CreditoPago::firstOrFail();
    $this->post(route('creditos.pagos.reverse', [$credito, $primero]), ['motivo' => 'No se admite corregir historia anterior.', 'confirmacion_qa' => true, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('pago');
});
