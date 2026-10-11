<?php

use App\Services\Credito\MotorAnticipos;
use App\Services\Credito\MotorPagos;

uses(Tests\TestCase::class);

function escenarioAnticipo(string $modo, string $metodo = 'capital_fijo', string $tasa = '0'): array
{
    $p = ['politica_mora' => ['gracia' => 'efectiva', 'intereses' => 'ambos'], 'dias_gracia_mora' => 3,
        'tasa_ordinaria_anual' => $tasa, 'tasa_moratoria_anual' => '72', 'comisiones' => [],
        'fiscalidad' => ['ordinario' => ['tratamiento' => 'exento'], 'moratorio' => ['tratamiento' => 'exento']],
        'reglas' => ['permite_prepago_parcial' => true, 'permite_liquidacion_anticipada' => true, 'monto_minimo_prepago' => '100', 'aplicacion_prepago' => $modo]];
    $tabla = ['metodo' => $metodo, 'escenario' => ['fecha_disposicion' => '2026-01-01', 'monto_solicitado' => '3000.00'], 'tabla' => [
        ['numero' => 1, 'fecha' => '2026-02-01', 'capital' => '1000.00', 'interes' => '0.00'],
        ['numero' => 2, 'fecha' => '2026-03-01', 'capital' => '1000.00', 'interes' => '0.00'],
        ['numero' => 3, 'fecha' => '2026-04-01', 'capital' => '1000.00', 'interes' => '0.00'],
    ]];
    $state = app(MotorPagos::class)->avanzar(app(MotorPagos::class)->iniciar($p, $tabla), '2026-01-11');

    return [$state, $tabla];
}

test('anticipo with zero rate conserves principal and changes only contractual mode', function ($modo, $metodo, $count, $first) {
    [$state, $tabla] = escenarioAnticipo($modo, $metodo);
    $r = app(MotorAnticipos::class)->calcular($state, $tabla, 'prepago', '1500.00');
    expect($r['despues']['capital_insoluto'])->toBe('1500.00')->and($r['capital_anticipado'])->toBe('1500.00')
        ->and($r['cronograma']['tabla'])->toHaveCount($count)->and($r['cronograma']['tabla'][0]['capital'])->toBe($first);
    expect($r['estado']['cronograma_version'])->toBe(2);
})->with([['reducir_plazo', 'capital_fijo', 2, '1000.00'], ['reducir_pago', 'capital_fijo', 3, '500.00'], ['reducir_plazo', 'cuota_nivelada', 2, '1000.00'], ['reducir_pago', 'cuota_nivelada', 3, '500.00']]);

test('anticipo covers midperiod accrued interest first and does not create future interest debt', function () {
    [$state, $tabla] = escenarioAnticipo('reducir_pago', tasa: '36');
    $r = app(MotorAnticipos::class)->calcular($state, $tabla, 'prepago', '1030.00');
    expect($r['capital_anticipado'])->toBe('1000.00')->and($r['despues']['capital_insoluto'])->toBe('2000.00');
    $next = app(MotorPagos::class)->avanzar($r['estado'], '2026-01-12');
    $generado = array_reduce($next['cargos'], fn ($s, $c) => $c['tipo'] === 'ordinario' && ($c['vigente'] ?? true) ? \App\Support\Decimal::add($s, $c['generado']) : $s, '0');
    expect(\App\Support\Decimal::round($generado))->toBe('2.00');
});

test('liquidacion pays current accrual and leaves no future accrual', function () {
    [$state, $tabla] = escenarioAnticipo('reducir_pago', tasa: '36');
    $r = app(MotorAnticipos::class)->calcular($state, $tabla, 'liquidacion', null);
    expect($r['importe'])->toBe('3030.00')->and($r['despues']['estado_operativo'])->toBe('liquidado');
    $later = app(MotorPagos::class)->resumen(app(MotorPagos::class)->avanzar($r['estado'], '2027-01-01'));
    expect($later['capital_insoluto'])->toBe('0.00')->and($later['exigible'])->toBe('0.00');
});

test('commission based on anticipated principal solves received total exactly', function () {
    [$state,$tabla] = escenarioAnticipo('reducir_pago');
    $state['producto']['comisiones'] = [['momento_cobro' => 'evento', 'evento_cobro' => 'prepago', 'importe' => '10', 'tipo_importe' => 'porcentaje', 'base_calculo' => 'capital_anticipado', 'obligatoria' => true, 'fiscalidad' => ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'], 'concepto' => ['nombre' => 'Anticipo']]];
    $r = app(MotorAnticipos::class)->calcular($state, $tabla, 'prepago', '1116.00');
    expect($r['capital_anticipado'])->toBe('1000.00')->and($r['despues']['capital_insoluto'])->toBe('2000.00');
    expect(fn () => app(MotorAnticipos::class)->calcular($state, $tabla, 'prepago', '1116.05'))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('recalculation conserves decimal principal over real dates taxes and both methods', function ($modo, $metodo, $fechas) {
    [$state] = escenarioAnticipo($modo, $metodo, '35.99999999');
    $inicio = \Carbon\CarbonImmutable::parse('2028-01-31', 'UTC');
    $calculo = app(\App\Services\Credito\TablaAmortizacionCreditoSimple::class)->calcular('3000.00', '35.99999999', $inicio,
        array_map(fn ($f) => \Carbon\CarbonImmutable::parse($f, 'UTC'), $fechas), \App\Enums\MetodoAmortizacion::from($metodo));
    $tabla = ['metodo' => $metodo, 'cuota_base' => $metodo === 'capital_fijo' ? $calculo['tabla'][0]['capital'] : $calculo['cuota_redondeada'],
        'escenario' => ['fecha_disposicion' => '2028-01-31', 'monto_solicitado' => '3000.00'], 'tabla' => $calculo['tabla']];
    $p = $state['producto'];
    $p['fiscalidad']['ordinario'] = ['tratamiento' => 'gravado', 'tasa' => '16.12345678', 'base' => 'importe_concepto'];
    $state = app(MotorPagos::class)->avanzar(app(MotorPagos::class)->iniciar($p, $tabla), '2028-02-02');
    $r = app(MotorAnticipos::class)->calcular($state, $tabla, 'prepago', '1500.00');
    $capitalPagado = array_reduce($r['asignaciones'], fn ($s, $a) => $a['tipo'] === 'capital' ? \App\Support\Decimal::add($s, $a['importe'], 2) : $s, '0.00');
    expect(\App\Support\Decimal::add($capitalPagado, $r['despues']['capital_insoluto'], 2))->toBe('3000.00');
    $sum = array_reduce($r['cronograma']['tabla'], fn ($s, $f) => \App\Support\Decimal::add($s, $f['capital'], 2), '0.00');
    expect($sum)->toBe($r['despues']['capital_insoluto']);
    expect(end($r['cronograma']['tabla'])['saldo_final'])->toBe('0.00');
    foreach ($r['cronograma']['tabla'] as $f) {
        expect(in_array($f['fecha'], $fechas, true))->toBeTrue();
    }
    if ($modo === 'reducir_pago') {
        expect(count($r['cronograma']['tabla']))->toBe(count($fechas));
    }
})->with(['reducir_pago', 'reducir_plazo'])->with(['capital_fijo', 'cuota_nivelada'])->with([
    [['2028-02-07', '2028-02-14', '2028-02-21']],
    [['2028-02-15', '2028-02-29', '2028-03-15']],
    [['2028-02-29', '2028-03-31', '2028-04-30']],
]);

test('settlement covers arrears mora taxes due fees and excludes periodic fees on removed dates', function () {
    [$state, $tabla] = escenarioAnticipo('reducir_plazo', tasa: '36');
    $p = $state['producto'];
    $p['fiscalidad']['moratorio'] = ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'];
    foreach ($tabla['tabla'] as &$fila) {
        $fila['comisiones_detalle'] = [['concepto' => 'Administración', 'importe' => '100.00', 'modalidad' => null, 'fiscalidad' => ['tratamiento' => 'exento']]];
    }
    unset($fila);
    $state = app(MotorPagos::class)->avanzar(app(MotorPagos::class)->iniciar($p, $tabla), '2026-02-06');
    $r = app(MotorAnticipos::class)->calcular($state, $tabla, 'liquidacion', null);
    $fees = array_filter($r['asignaciones'], fn ($a) => $a['tipo'] === 'comision');
    expect(array_column($fees, 'total'))->toBe(['100.00']);
    expect(array_sum(array_column(array_filter($r['asignaciones'], fn ($a) => $a['tipo'] === 'moratorio'), 'impuesto')))->toBeGreaterThan(0);
    expect($r['despues']['exigible'])->toBe('0.00')->and($r['cronograma']['tabla'])->toBe([]);
});

test('event fee bases are explicit and principal minimum applies after accrued debt and fees', function (string $base, string $importe, string $capital) {
    [$state, $tabla] = escenarioAnticipo('reducir_pago', tasa: '36');
    $state['producto']['comisiones'] = [['momento_cobro' => 'evento', 'evento_cobro' => 'prepago', 'importe' => '10', 'tipo_importe' => 'porcentaje', 'base_calculo' => $base,
        'obligatoria' => true, 'fiscalidad' => ['tratamiento' => 'exento'], 'concepto' => ['nombre' => 'Cargo contractual']]];
    $r = app(MotorAnticipos::class)->calcular($state, $tabla, 'prepago', $importe);
    expect($r['capital_anticipado'])->toBe($capital);
    $state['producto']['reglas']['monto_minimo_prepago'] = '1100';
    expect(fn () => app(MotorAnticipos::class)->calcular($state, $tabla, 'prepago', $importe))->toThrow(\Illuminate\Validation\ValidationException::class);
})->with([['monto_credito', '1330.00', '1000.00'], ['capital_insoluto', '1330.00', '1000.00'], ['capital_anticipado', '1130.00', '1000.00']]);

test('settlement rejects excess and ambiguous historical fees and applies optional charges only explicitly', function () {
    [$state, $tabla] = escenarioAnticipo('reducir_pago');
    expect(fn () => app(MotorAnticipos::class)->calcular($state, $tabla, 'liquidacion', '3000.01'))->toThrow(\Illuminate\Validation\ValidationException::class);
    $state['producto']['comisiones'] = [['momento_cobro' => 'liquidacion', 'importe' => '100', 'tipo_importe' => 'fijo', 'base_calculo' => 'no_aplica',
        'obligatoria' => false, 'fiscalidad' => ['tratamiento' => 'exento'], 'concepto' => ['nombre' => 'Liquidación']]];
    expect(fn () => app(MotorAnticipos::class)->calcular($state, $tabla, 'liquidacion', null))->toThrow(\Illuminate\Validation\ValidationException::class);
    $state['producto']['comisiones'][0]['evento_cobro'] = 'liquidacion_anticipada';
    expect(app(MotorAnticipos::class)->calcular($state, $tabla, 'liquidacion', null)['importe'])->toBe('3000.00');
    expect(app(MotorAnticipos::class)->calcular($state, $tabla, 'liquidacion', null, [0])['importe'])->toBe('3100.00');
    expect(fn () => app(MotorAnticipos::class)->calcular($state, $tabla, 'prepago', '1000.00', [0]))->toThrow(\Illuminate\Validation\ValidationException::class);
});
