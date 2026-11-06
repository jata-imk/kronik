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
function creditoBulletSintetico($test, string $base): array
{
    [$actor, $solicitud, $paquete] = prepararDesembolsoQa($test, pagos: true);
    $producto = ['motor_calculo_version' => 2, 'politica_mora' => ['gracia' => 'efectiva', 'intereses' => 'ambos'],
        'dias_gracia_mora' => 0, 'tasa_ordinaria_anual' => '24', 'tasa_moratoria_anual' => '36', 'comisiones' => [],
        'reglas' => ['metodos_amortizacion' => ['bullet'], 'politica_interes_bullet' => 'periodicos', 'base_ordinaria' => 'saldo_insoluto',
            'base_moratoria' => $base, 'aplicacion_prepago' => 'conservar_fecha'],
        'fiscalidad' => ['ordinario' => ['tratamiento' => 'no_causa'], 'moratorio' => ['tratamiento' => 'no_causa']]];
    $condiciones = ['producto' => $producto];
    $credito = Credito::create(['solicitud_id' => $solicitud->id, 'solicitud_formalizacion_id' => $paquete->formalizacion->id,
        'cliente_id' => $solicitud->cliente_id, 'sucursal_id' => $solicitud->sucursal_id, 'responsable_id' => $actor->id,
        'producto_version_id' => $solicitud->producto_version_id, 'fecha_desembolso' => '2027-01-01', 'capital_inicial' => '12000',
        'estado' => 'activo', 'modo' => 'qa', 'condiciones' => $condiciones,
        'condiciones_hash' => hash('sha256', json_encode($condiciones, JSON_THROW_ON_ERROR))]);
    $tabla = ['metodo' => 'bullet', 'motor_calculo_version' => 2, 'escenario' => ['fecha_disposicion' => '2027-01-01'], 'tabla' => [
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
