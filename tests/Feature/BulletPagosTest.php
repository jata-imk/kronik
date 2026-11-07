<?php

use App\Enums\ActivityEvent;
use App\Models\Activity;
use App\Models\Credito;
use App\Models\CreditoPago;
use App\Services\Credito\PagoService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(fn () => $this->withoutVite());

// Evidencia sintética insertada exclusivamente en la BD de pruebas: no activa ni origina un producto Bullet.
function creditoBulletSintetico($test, string $base, string $politica = 'periodicos'): array
{
    [$actor, $solicitud, $paquete] = prepararDesembolsoQa($test, pagos: true);
    $producto = ['motor_calculo_version' => 2, 'politica_mora' => ['gracia' => 'efectiva', 'intereses' => 'ambos'],
        'dias_gracia_mora' => 0, 'tasa_ordinaria_anual' => '24', 'tasa_moratoria_anual' => '36', 'comisiones' => [],
        'reglas' => ['metodos_amortizacion' => ['bullet'], 'politica_interes_bullet' => $politica, 'base_ordinaria' => 'saldo_insoluto',
            'base_moratoria' => $base, 'aplicacion_prepago' => 'conservar_fecha', 'permite_prepago_parcial' => true,
            'permite_liquidacion_anticipada' => true, 'monto_minimo_prepago' => '100'],
        'fiscalidad' => ['ordinario' => ['tratamiento' => 'no_causa'], 'moratorio' => ['tratamiento' => 'no_causa']]];
    $condiciones = ['producto' => $producto];
    $credito = Credito::create(['solicitud_id' => $solicitud->id, 'solicitud_formalizacion_id' => $paquete->formalizacion->id,
        'cliente_id' => $solicitud->cliente_id, 'sucursal_id' => $solicitud->sucursal_id, 'responsable_id' => $actor->id,
        'producto_version_id' => $solicitud->producto_version_id, 'fecha_desembolso' => '2027-01-01', 'capital_inicial' => '12000',
        'estado' => 'activo', 'modo' => 'qa', 'condiciones' => $condiciones,
        'condiciones_hash' => hash('sha256', json_encode($condiciones, JSON_THROW_ON_ERROR))]);
    $tabla = ['metodo' => 'bullet', 'motor_calculo_version' => 2, 'cuota_base' => '0.00',
        'escenario' => ['fecha_disposicion' => '2027-01-01', 'monto_solicitado' => '12000.00'], 'tabla' => [
            ['numero' => 1, 'fecha' => '2027-02-01', 'capital' => '0.00'], ['numero' => 2, 'fecha' => '2027-03-01', 'capital' => '12000.00']]];
    $credito->cronogramas()->create(['version' => 1, 'snapshot' => $tabla, 'snapshot_hash' => hash('sha256', json_encode($tabla, JSON_THROW_ON_ERROR))]);
    $test->travelTo(CarbonImmutable::parse('2027-02-01 12:00:00', 'America/Mexico_City'));
    config(['originacion.pagos_qa_habilitados' => true]);

    return [$actor, $credito];
}

test('obligaciones Bullet persisten pago y reverso una vez con actor contexto e historia inmutable', function (string $base, string $exigible) {
    [$actor, $credito] = creditoBulletSintetico($this, $base);
    $service = app(PagoService::class);
    $eventos = Activity::count();
    $hash = $credito->condiciones_hash;
    $cronograma = $credito->cronogramas()->sole()->getRawOriginal();
    $data = ['fecha_pago' => '2027-02-01', 'importe' => '100', 'referencia' => 'BULLET-RESERVADA-'.Str::uuid(),
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $previa = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->assertJsonMissingPath('snapshot')->json();
    expect($previa['despues']['capital_insoluto'])->toBe('12000.00')
        ->and(Activity::count())->toBe($eventos);
    $this->assertDatabaseCount('credito_pagos', 0);
    $this->assertDatabaseCount('credito_movimientos', 0);
    $data['previa_hash'] = $previa['previa_hash'];
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $pago = CreditoPago::sole();
    $this->post(route('creditos.pagos.store', $credito), $data)->assertRedirect(route('creditos.pagos.show', [$credito, $pago]));
    $this->assertDatabaseCount('credito_pagos', 1);
    expect(DB::table('credito_pagos')->value('snapshot'))->not->toContain('12000.00');
    $this->travelTo(CarbonImmutable::parse('2027-02-02 12:00:00', 'America/Mexico_City'));
    $conteos = [Activity::count(), $credito->movimientos()->count()];
    $resumen = $service->consultar($credito)['resumen'];
    expect($resumen['interes_vencido'])->toBe('148.00')
        ->and($resumen['capital_vencido'])->toBe('0.00')
        ->and($resumen['exigible'])->toBe($exigible)
        ->and([Activity::count(), $credito->movimientos()->count()])->toBe($conteos);
    $reverso = ['motivo' => 'Corrección reservada del pago sintético Bullet.', 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), $reverso)->assertSessionHasNoErrors();
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), $reverso)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('credito_pagos', 2);
    $compensacion = CreditoPago::where('tipo', 'reverso')->sole();
    expect($compensacion->snapshot['despues'])->toBe($pago->snapshot['antes'])
        ->and($service->consultar($credito)['resumen']['interes_vencido'])->toBe('248.00')
        ->and($credito->fresh()->condiciones_hash)->toBe($hash)
        ->and($credito->cronogramas()->sole()->getRawOriginal())->toBe($cronograma);
    foreach ([ActivityEvent::CreditPaymentRecorded, ActivityEvent::CreditPaymentReversed] as $codigo) {
        $evento = Activity::where('event', $codigo->value)->sole();
        expect($evento->causer_id)->toBe($actor->id)->and($evento->subject_id)->toBe($credito->id)
            ->and($evento->team_id)->toBe($actor->current_team_id)->and($evento->sucursal_id)->toBe($credito->sucursal_id);
    }
    assertDomainAudit([ActivityEvent::CreditPaymentRecorded->value, ActivityEvent::CreditPaymentReversed->value], $actor, $credito->sucursal_id,
        [$data['referencia'], $data['idempotency_key'], $reverso['motivo'], '12000.00', 'snapshot', 'base_moratoria']);
})->with([['obligacion_vencida', '148.15'], ['saldo_insoluto', '160.00'], ['capital_inicial', '160.00']]);

test('anticipo y liquidación Bullet conservan devengado contadores contexto e historia al reabrir', function (string $politica, string $base) {
    [$actor, $credito] = creditoBulletSintetico($this, $base, $politica);
    config(['originacion.anticipos_qa_habilitados' => true]);
    $this->travelTo(CarbonImmutable::parse('2027-01-16 12:00:00', 'America/Mexico_City'));
    $service = app(PagoService::class);
    $original = $credito->cronogramas()->sole()->getRawOriginal();
    $condiciones = $credito->getRawOriginal('condiciones');
    $eventos = Activity::count();
    $data = ['operacion' => 'prepago', 'fecha_pago' => '2027-01-16', 'importe' => '3120',
        'referencia' => 'BULLET-ANTICIPO-RESERVADO-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $previa = $this->postJson(route('creditos.anticipos.preview', $credito), $data)->assertOk()->assertJsonMissingPath('snapshot')->json();
    expect($previa['capital_anticipado'])->toBe('3120.00')
        ->and($previa['despues']['capital_insoluto'])->toBe('8880.00')
        ->and($previa['proyeccion']['intereses'])->toBe('380.48')
        ->and(Activity::count())->toBe($eventos);
    $this->assertDatabaseCount('credito_pagos', 0);
    $this->assertDatabaseCount('credito_movimientos', 0);
    $this->assertDatabaseCount('credito_cronogramas', 1);
    $data['previa_hash'] = $previa['previa_hash'];
    $obsoleta = [...$data, 'idempotency_key' => (string) Str::uuid(), 'referencia' => 'OTRO-'.Str::uuid()];
    $obsoleta['previa_hash'] = $this->postJson(route('creditos.anticipos.preview', $credito), $obsoleta)->assertOk()->json('previa_hash');
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    $anticipo = CreditoPago::sole();
    $anticipoOriginal = $anticipo->getRawOriginal();
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    $this->post(route('creditos.anticipos.store', $credito), $obsoleta)->assertSessionHasErrors('pago');
    $this->post(route('creditos.anticipos.store', $credito), [...$data, 'importe' => '3121'])->assertSessionHasErrors('pago');
    $this->assertDatabaseCount('credito_pagos', 1);
    $this->assertDatabaseCount('credito_cronogramas', 2);
    $clave = ($politica === 'periodicos' ? '1' : '2').':ordinario';
    expect($anticipo->snapshot['despues']['cargos'][$clave]['contabilizado'])->toBe('120.00')
        ->and($anticipo->snapshot['despues']['cargos'][$clave]['pagado'])->toBe('0.00')
        ->and($anticipo->snapshot['despues']['cargos'][$clave]['fecha'])->toBe($politica === 'periodicos' ? '2027-02-01' : '2027-03-01')
        ->and(DB::table('credito_pagos')->value('snapshot'))->not->toContain('8880.00');
    $this->travelTo(CarbonImmutable::parse('2027-01-20 12:00:00', 'America/Mexico_City'));
    $conteos = [Activity::count(), $credito->movimientos()->count(), $credito->cronogramas()->count()];
    expect($service->consultar($credito)['resumen']['capital_insoluto'])->toBe('8880.00')
        ->and([Activity::count(), $credito->movimientos()->count(), $credito->cronogramas()->count()])->toBe($conteos);
    $liquidacion = [...$data, 'operacion' => 'liquidacion', 'fecha_pago' => '2027-01-20', 'importe' => null,
        'referencia' => 'BULLET-LIQUIDACION-RESERVADA-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid()];
    $p = $this->postJson(route('creditos.anticipos.preview', $credito), $liquidacion)->assertOk()->assertJsonMissingPath('snapshot')->json();
    expect($p['importe'])->toBe('9023.68')
        ->and([Activity::count(), $credito->movimientos()->count(), $credito->cronogramas()->count()])->toBe($conteos);
    $liquidacion['importe'] = $p['importe'];
    $liquidacion['previa_hash'] = $p['previa_hash'];
    $this->post(route('creditos.anticipos.store', $credito), $liquidacion)->assertSessionHasNoErrors();
    $liquidado = CreditoPago::latest('id')->firstOrFail();
    $liquidadoOriginal = $liquidado->getRawOriginal();
    $this->post(route('creditos.anticipos.store', $credito), $liquidacion)->assertSessionHasNoErrors();
    $devengos = $credito->movimientos()->where('tipo', 'devengo')->pluck('importe')->all();
    expect(\App\Support\Decimal::round(array_reduce($devengos, fn ($sum, $importe) => \App\Support\Decimal::add($sum, (string) $importe), '0')))->toBe('143.68')
        ->and($service->consultar($credito)['resumen']['estado_operativo'])->toBe('liquidado');
    $reverso = ['motivo' => 'Corrección reservada de liquidación Bullet.', 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $this->post(route('creditos.pagos.reverse', [$credito, $anticipo]), [...$reverso, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('pago');
    $this->post(route('creditos.pagos.reverse', [$credito, $liquidado]), $reverso)->assertSessionHasNoErrors();
    $this->post(route('creditos.pagos.reverse', [$credito, $liquidado]), $reverso)->assertSessionHasNoErrors();
    $compensacion = CreditoPago::where('tipo', 'reverso')->sole();
    expect($compensacion->snapshot['despues'])->toBe([...$liquidado->snapshot['antes'], 'cronograma_version' => 4])
        ->and($service->consultar($credito)['resumen']['estado_operativo'])->toBe('activo')
        ->and($service->consultar($credito)['resumen']['capital_insoluto'])->toBe('8880.00')
        ->and($credito->fresh()->getRawOriginal('condiciones'))->toBe($condiciones)
        ->and($credito->cronogramas()->where('version', 1)->sole()->getRawOriginal())->toBe($original)
        ->and($anticipo->fresh()->getRawOriginal())->toBe($anticipoOriginal)
        ->and($liquidado->fresh()->getRawOriginal())->toBe($liquidadoOriginal);
    $this->assertDatabaseCount('credito_pagos', 3);
    $this->assertDatabaseCount('credito_cronogramas', 4);
    foreach ($credito->movimientos()->where('credito_pago_id', $liquidado->id)->get() as $movimiento) {
        expect($credito->movimientos()->where('credito_pago_id', $compensacion->id)->where('concepto', $movimiento->concepto)
            ->where('importe', \App\Support\Decimal::sub('0', $movimiento->importe, 2))->exists())->toBeTrue();
    }
    $auditoria = Activity::whereIn('event', [ActivityEvent::CreditPaymentRecorded->value, ActivityEvent::CreditPaymentReversed->value])->get();
    expect($auditoria)->toHaveCount(3);
    foreach ($auditoria as $evento) {
        expect($evento->causer_id)->toBe($actor->id)->and($evento->subject_id)->toBe($credito->id)
            ->and($evento->team_id)->toBe($actor->current_team_id)->and($evento->sucursal_id)->toBe($credito->sucursal_id);
    }
    assertDomainAudit([ActivityEvent::CreditPaymentRecorded->value, ActivityEvent::CreditPaymentReversed->value], $actor, $credito->sucursal_id,
        [$data['referencia'], $data['idempotency_key'], $liquidacion['referencia'], $reverso['motivo'], '8880.00', 'snapshot', 'generado_numerador']);
})->with(['periodicos', 'al_vencimiento'])->with(['obligacion_vencida', 'saldo_insoluto', 'capital_inicial']);

test('reverso de anticipo Bullet restaura principal y vuelve a devengar sin perder su interés anterior', function (string $politica) {
    [, $credito] = creditoBulletSintetico($this, 'capital_inicial', $politica);
    config(['originacion.anticipos_qa_habilitados' => true]);
    $this->travelTo(CarbonImmutable::parse('2027-01-16 12:00:00', 'America/Mexico_City'));
    $data = ['operacion' => 'prepago', 'fecha_pago' => '2027-01-16', 'importe' => '3120', 'referencia' => 'ANTICIPO-'.Str::uuid(),
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $data['previa_hash'] = $this->postJson(route('creditos.anticipos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    $anticipo = CreditoPago::sole();
    $this->post(route('creditos.pagos.reverse', [$credito, $anticipo]), ['motivo' => 'Corrección sintética de anticipo Bullet.',
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true])->assertSessionHasNoErrors();
    $reverso = CreditoPago::where('tipo', 'reverso')->sole();
    expect($reverso->snapshot['despues'])->toBe([...$anticipo->snapshot['antes'], 'cronograma_version' => 3]);
    $this->travelTo(CarbonImmutable::parse('2027-01-20 12:00:00', 'America/Mexico_City'));
    $nueva = [...$data, 'operacion' => 'liquidacion', 'fecha_pago' => '2027-01-20', 'importe' => null,
        'referencia' => 'COTIZACION-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid()];
    $this->postJson(route('creditos.anticipos.preview', $credito), $nueva)->assertOk()->assertJsonPath('importe', '12152.00');
})->with(['periodicos', 'al_vencimiento']);

test('operaciones internas Bullet respetan permisos separados lectura y sucursal incluso para Super Admin', function () {
    [$actor, $credito] = creditoBulletSintetico($this, 'obligacion_vencida');
    config(['originacion.anticipos_qa_habilitados' => true]);
    $this->seed(\Database\Seeders\ModulesAndPermissionsSeeder::class);
    $actor->forceFill(['is_super_admin' => false])->save();
    $actor->givePermissionTo(['read creditos', 'read solicitudes', 'read clientes', 'pay creditos']);
    $data = ['operacion' => 'prepago', 'fecha_pago' => '2027-02-01', 'importe' => '3248', 'referencia' => 'PERMISOS-'.Str::uuid(),
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $this->postJson(route('creditos.anticipos.preview', $credito), $data)->assertForbidden();
    $actor->givePermissionTo('prepay creditos');
    $this->postJson(route('creditos.anticipos.preview', $credito), [...$data, 'operacion' => 'liquidacion'])->assertForbidden();
    $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertUnprocessable()->assertJsonValidationErrors('operacion');
    $data['previa_hash'] = $this->postJson(route('creditos.anticipos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    $pago = CreditoPago::sole();
    $actor->revokePermissionTo('prepay creditos');
    $conteos = [$credito->pagos()->count(), $credito->movimientos()->count(), Activity::count()];
    $this->get(route('creditos.pagos.show', [$credito, $pago]))->assertOk();
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), ['motivo' => 'Sin permiso para revertir.', 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true])->assertForbidden();
    $otra = \App\Models\Sucursal::create(['nombre' => 'Sucursal ajena', 'clave' => 'AJENA-'.Str::random(8), 'activa' => true]);
    $actor->forceFill(['is_super_admin' => true, 'current_sucursal_id' => $otra->id])->save();
    $this->postJson(route('creditos.anticipos.preview', $credito), $data)->assertUnprocessable()->assertJsonValidationErrors('pago');
    expect([$credito->pagos()->count(), $credito->movimientos()->count(), Activity::count()])->toBe($conteos);
});
