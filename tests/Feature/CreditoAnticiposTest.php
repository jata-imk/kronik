<?php

use App\Models\CreditoPago;
use Illuminate\Support\Str;

test('anticipo previews become stale and archive fees stay unchanged on a later regular payment', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    config(['originacion.anticipos_qa_habilitados' => true]);
    $data = [...$data, 'operacion' => 'prepago', 'importe' => '2500.00'];
    $data['previa_hash'] = $this->postJson(route('creditos.anticipos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $stale = [...$data, 'idempotency_key' => (string) Str::uuid(), 'referencia' => 'OTRO-'.Str::uuid()];
    $stale['previa_hash'] = $this->postJson(route('creditos.anticipos.preview', $credito), $stale)->assertOk()->json('previa_hash');
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    $this->post(route('creditos.anticipos.store', $credito), $stale)->assertSessionHasErrors('pago');
    $before = CreditoPago::firstOrFail()->snapshot['despues'];
    $next = $credito->cronogramas()->where('version', 2)->firstOrFail()->snapshot['tabla'][0]['fecha'];
    $this->travelTo(\Carbon\CarbonImmutable::parse($next.' 12:00:00', 'America/Mexico_City'));
    $pago = [...$data, 'operacion' => 'pago', 'fecha_pago' => $next, 'importe' => '100.00', 'idempotency_key' => (string) Str::uuid(), 'referencia' => 'POST-'.Str::uuid()];
    $pago['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $pago)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credito), $pago)->assertSessionHasNoErrors();
    $after = CreditoPago::latest('id')->firstOrFail()->snapshot['despues'];
    foreach ($before['cargos'] as $key => $cargo) {
        if (($cargo['vigente'] ?? true) === false) {
            expect($after['cargos'][$key])->toBe($cargo);
        }
    }
    $this->assertDatabaseCount('credito_cronogramas', 2);
});

test('anticipo permissions are independent and cannot be injected into the regular payment route', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    config(['originacion.anticipos_qa_habilitados' => true]);
    $this->seed(\Database\Seeders\ModulesAndPermissionsSeeder::class);
    $actor->forceFill(['is_super_admin' => false])->save();
    $actor->givePermissionTo(['read creditos', 'read solicitudes', 'read clientes', 'pay creditos']);
    $this->postJson(route('creditos.anticipos.preview', $credito), [...$data, 'operacion' => 'prepago'])->assertForbidden();
    $this->postJson(route('creditos.pagos.preview', $credito), [...$data, 'operacion' => 'prepago'])->assertUnprocessable()->assertJsonValidationErrors('operacion');
    $actor->givePermissionTo('prepay creditos');
    $r = $this->postJson(route('creditos.anticipos.preview', $credito), ['operacion' => 'prepago'])->assertUnprocessable();
    foreach (collect($r->json('errors'))->flatten() as $m) {
        expect($m)->not->toContain('validation.');
    }
    $this->postJson(route('creditos.anticipos.preview', $credito), [...$data, 'operacion' => 'liquidacion'])->assertForbidden();
    $this->assertDatabaseCount('credito_pagos', 0);
});

test('QA anticipo and liquidation conserve schedule history and reversal reopens loan', function () {
    [$actor,$credito,$data] = creditoPagableQa($this);
    config(['originacion.anticipos_qa_habilitados' => true]);
    $data['operacion'] = 'prepago';
    $data['importe'] = '2500.00';
    $previa = $this->postJson(route('creditos.anticipos.preview', $credito), $data)->assertOk()->json();
    $data['previa_hash'] = $previa['previa_hash'];
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('credito_cronogramas', 2);
    $pago = CreditoPago::firstOrFail();
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('credito_pagos', 1);
    $liquidacion = [...$data, 'operacion' => 'liquidacion', 'importe' => null, 'referencia' => 'LIQ-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid()];
    $p = $this->postJson(route('creditos.anticipos.preview', $credito), $liquidacion)->assertOk()->json();
    $liquidacion['importe'] = $p['importe'];
    $liquidacion['previa_hash'] = $p['previa_hash'];
    $this->post(route('creditos.anticipos.store', $credito), $liquidacion)->assertSessionHasNoErrors();
    $liquidado = CreditoPago::latest('id')->first();
    expect(app(\App\Services\Credito\PagoService::class)->consultar($credito)['resumen']['estado_operativo'])->toBe('liquidado');
    $this->post(route('creditos.pagos.reverse', [$credito, $liquidado]), ['motivo' => 'Corrección sintética de liquidación.', 'confirmacion_qa' => true, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasNoErrors();
    expect(app(\App\Services\Credito\PagoService::class)->consultar($credito)['resumen']['estado_operativo'])->toBe('activo');
    $this->assertDatabaseCount('credito_cronogramas',4);
});
