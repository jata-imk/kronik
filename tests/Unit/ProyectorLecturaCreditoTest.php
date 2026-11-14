<?php

use App\Services\Credito\MotorPagos;
use App\Services\Credito\ProyectorLecturaCredito;

uses(Tests\TestCase::class);

function escenarioLectura(string $metodo = 'bullet', string $politica = 'periodicos'): array
{
    $producto = ['motor_calculo_version' => 2, 'politica_mora' => ['gracia' => 'efectiva', 'intereses' => 'ambos'],
        'dias_gracia_mora' => 3, 'tasa_ordinaria_anual' => '24', 'tasa_moratoria_anual' => '36',
        'reglas' => ['metodos_amortizacion' => [$metodo], 'politica_interes_bullet' => $politica, 'base_moratoria' => 'obligacion_vencida', 'base_ordinaria' => 'saldo_insoluto', 'aplicacion_prepago' => 'conservar_fecha'],
        'fiscalidad' => ['ordinario' => ['tratamiento' => 'no_causa'], 'moratorio' => ['tratamiento' => 'no_causa']]];
    $tabla = ['metodo' => $metodo, 'motor_calculo_version' => 2, 'escenario' => ['fecha_disposicion' => '2027-01-01'], 'tabla' => [
        ['numero' => 1, 'fecha' => '2027-02-01', 'capital' => $metodo === 'bullet' ? '0.00' : '6000.00', 'interes' => $politica === 'periodicos' ? '248.00' : '0.00'],
        ['numero' => 2, 'fecha' => '2027-03-01', 'capital' => $metodo === 'bullet' ? '12000.00' : '6000.00', 'interes' => $politica === 'periodicos' ? '224.00' : '472.00'],
    ]];

    return [app(MotorPagos::class)->iniciar($producto, $tabla), $tabla];
}

test('lectura Bullet diferencia exigible vencido no exigible y gracia por fecha contractual', function () {
    [$estado, $tabla] = escenarioLectura();
    $proyector = app(ProyectorLecturaCredito::class);
    $vence = $proyector->calcular($estado, $tabla, '2027-02-01');
    expect($vence['capital'])->toBe('12000.00')->and($vence['exigible'])->toBe('248.00')->and($vence['vencido'])->toBe('0.00');
    $gracia = $proyector->calcular($estado, $tabla, '2027-02-02');
    expect($gracia['capital'])->toBe('12000.00')->and($gracia['vencido'])->toBe('248.00')->and($gracia['dias_atraso'])->toBe(1);
    $vencidas = collect($gracia['obligaciones'])->where('naturaleza', 'exigible');
    expect($vencidas->sum('capital'))->toEqual(0)->and($vencidas->sum('moratorio'))->toEqual(0);
    [$final, $tablaFinal] = escenarioLectura(politica: 'al_vencimiento');
    $lecturaFinal = $proyector->calcular($final, $tablaFinal, '2027-02-02');
    expect($lecturaFinal['vencido'])->toBe('0.00')->and($lecturaFinal['exigible'])->toBe('0.00')
        ->and(collect($lecturaFinal['obligaciones'])->where('naturaleza', 'programada')->sum('ordinario'))->toEqual(472);
});

test('pago parcial conserva capital e interes pendiente y avance por intervalos equivale al directo', function (string $metodo) {
    [$estado, $tabla] = escenarioLectura($metodo);
    $motor = app(MotorPagos::class);
    $estado = $motor->aplicar($motor->avanzar($estado, '2027-02-01'), '100.00')['estado'];
    $proyector = app(ProyectorLecturaCredito::class);
    $lectura = $proyector->calcular($estado, $tabla, '2027-02-02');
    // En simple continúa devengándose 4.00 sobre el principal vencido; Bullet lo asigna al siguiente cargo.
    expect($lectura['capital'])->toBe('12000.00')->and($lectura['vencido'])->toBe($metodo === 'bullet' ? '148.00' : '6152.00');
    $directo = $proyector->calcular($estado, $tabla, '2027-02-05');
    $intervalos = $proyector->calcular($lectura['estado_motor'], $tabla, '2027-02-05');
    expect($intervalos)->toEqual($directo);
})->with(['bullet', 'capital_fijo', 'cuota_nivelada']);
