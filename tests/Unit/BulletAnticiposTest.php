<?php

use App\Enums\ConvencionInteres;
use App\Enums\MetodoAmortizacion;
use App\Enums\PoliticaInteresBullet;
use App\Enums\VersionMotorCredito;
use App\Services\Credito\Amortizacion\CalculadorAmortizacion;
use App\Services\Credito\Amortizacion\CondicionesAmortizacion;
use App\Services\Credito\Amortizacion\ExigibilidadInteresBullet;
use App\Services\Credito\MotorAnticipos;
use App\Services\Credito\MotorPagos;
use App\Support\Decimal as D;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

function escenarioAnticipoBullet(string $politica = 'periodicos', string $base = 'obligacion_vencida', array $ajustes = [], array $fechas = ['2027-02-01', '2027-03-01'], string $inicio = '2027-01-01'): array
{
    $producto = array_replace_recursive([
        'motor_calculo_version' => 2, 'politica_mora' => ['gracia' => 'efectiva', 'intereses' => 'ambos'],
        'dias_gracia_mora' => 0, 'tasa_ordinaria_anual' => '24', 'tasa_moratoria_anual' => '36', 'comisiones' => [],
        'reglas' => ['metodos_amortizacion' => ['bullet'], 'convencion_interes' => 'dias_reales_360',
            'politica_interes_bullet' => $politica, 'base_ordinaria' => 'saldo_insoluto', 'base_moratoria' => $base,
            'aplicacion_prepago' => 'conservar_fecha', 'permite_prepago_parcial' => true,
            'permite_liquidacion_anticipada' => true, 'monto_minimo_prepago' => '100'],
        'fiscalidad' => ['ordinario' => ['tratamiento' => 'no_causa'], 'moratorio' => ['tratamiento' => 'no_causa']],
    ], $ajustes);
    $calculo = app(CalculadorAmortizacion::class)->calcular(new CondicionesAmortizacion('12000', $producto['tasa_ordinaria_anual'],
        CarbonImmutable::parse($inicio, 'UTC'), array_map(fn ($fecha) => CarbonImmutable::parse($fecha, 'UTC'), $fechas),
        MetodoAmortizacion::Bullet, VersionMotorCredito::Act360Racional, ConvencionInteres::DiasReales360));
    $tabla = [...app(ExigibilidadInteresBullet::class)->aplicar($calculo, PoliticaInteresBullet::from($politica), ConvencionInteres::DiasReales360)->toArray(),
        'metodo' => 'bullet', 'motor_calculo_version' => 2, 'convencion_interes' => 'dias_reales_360', 'cuota_base' => '0.00',
        'escenario' => ['fecha_disposicion' => $inicio, 'monto_solicitado' => '12000.00']];

    return [app(MotorPagos::class)->iniciar($producto, $tabla), $tabla];
}

test('anticipo Bullet conserva interés previo y fecha y proyecta únicamente el nuevo capital', function (string $politica, array $intereses, array $totales) {
    [$inicial, $tabla] = escenarioAnticipoBullet($politica);
    $pagos = app(MotorPagos::class);
    $antes = $pagos->avanzar($inicial, '2027-01-16');
    $copia = $antes;
    $clave = ($politica === 'periodicos' ? '1' : '2').':ordinario';
    $r = app(MotorAnticipos::class)->calcular($antes, $tabla, 'prepago', '3120');
    expect($antes)->toBe($copia)
        ->and($r['capital_anticipado'])->toBe('3120.00')
        ->and($r['despues']['capital_insoluto'])->toBe('8880.00')
        ->and($r['despues']['exigible'])->toBe('0.00')
        ->and($r['estado']['capital_inicial'])->toBe('12000.00')
        ->and(array_keys($r['estado']['cargos']))->toBe(array_keys($antes['cargos']))
        ->and($r['estado']['cargos'][$clave]['generado_numerador'])->toBe($antes['cargos'][$clave]['generado_numerador'])
        ->and($r['estado']['cargos'][$clave]['fecha'])->toBe($antes['cargos'][$clave]['fecha'])
        ->and($r['estado']['cargos'][$clave]['pagado'])->toBe('0.00')
        ->and($r['estado']['cargos'][$clave]['contabilizado'])->toBe('120.00')
        ->and($r['estado']['cargos'][$clave]['vigente'] ?? true)->toBeTrue()
        ->and($r['estado']['cargos']['2:capital']['fecha'])->toBe('2027-03-01')
        ->and(array_column($r['asignaciones'], 'tipo'))->toBe(['capital'])
        ->and(array_column($r['cronograma']['tabla'], 'numero'))->toBe([1, 2])
        ->and(array_column($r['cronograma']['tabla'], 'interes'))->toBe($intereses)
        ->and(array_column($r['cronograma']['tabla'], 'pago_total'))->toBe($totales)
        ->and($r['proyeccion']['intereses'])->toBe('380.48');
    $siguiente = $pagos->avanzar($r['estado'], '2027-01-17');
    expect(D::sub($siguiente['cargos'][$clave]['generado'], $r['estado']['cargos'][$clave]['generado'], 2))->toBe('5.92');
    $state = $r['estado'];
    foreach ($r['cronograma']['tabla'] as $fila) {
        $state = $pagos->avanzar($state, $fila['fecha']);
        expect($pagos->resumen($state)['exigible'])->toBe($fila['pago_total']);
        if (D::compare($fila['pago_total'], '0') > 0) {
            $state = $pagos->aplicar($state, $fila['pago_total'])['estado'];
        }
    }
    expect($pagos->resumen($state)['capital_insoluto'])->toBe('0.00');
})->with([
    ['periodicos', ['214.72', '165.76'], ['214.72', '9045.76']],
    ['al_vencimiento', ['0.00', '380.48'], ['0.00', '9260.48']],
]);

test('anticipos Bullet sucesivos incluso el mismo día conservan el acumulador y su contabilización', function (string $politica) {
    [$inicial, $tabla] = escenarioAnticipoBullet($politica);
    $pagos = app(MotorPagos::class);
    $anticipos = app(MotorAnticipos::class);
    $state = $pagos->avanzar($inicial, '2027-01-16');
    $unico = $anticipos->calcular($state, $tabla, 'prepago', '3120');
    $primero = $anticipos->calcular($state, $tabla, 'prepago', '1000');
    $segundo = $anticipos->calcular($primero['estado'], $primero['cronograma'], 'prepago', '2120');
    expect($segundo['cronograma'])->toBe($unico['cronograma'])
        ->and($segundo['estado']['cargos'])->toBe($unico['estado']['cargos'])
        ->and($segundo['estado']['cronograma_version'])->toBe(3);
    $tercero = $anticipos->calcular($pagos->avanzar($segundo['estado'], '2027-01-20'), $segundo['cronograma'], 'prepago', '1000');
    expect($tercero['despues']['capital_insoluto'])->toBe('7880.00')
        ->and($tercero['proyeccion']['intereses'])->toBe('353.81')
        ->and($tercero['estado']['capital_inicial'])->toBe('12000.00');
})->with(['periodicos', 'al_vencimiento']);

test('anticipo Bullet cubre el interés exigible restante antes del principal', function (string $base) {
    [$inicial, $tabla] = escenarioAnticipoBullet(base: $base);
    $pagos = app(MotorPagos::class);
    $antes = $pagos->aplicar($pagos->avanzar($inicial, '2027-02-01'), '100')['estado'];
    $r = app(MotorAnticipos::class)->calcular($antes, $tabla, 'prepago', '3148');
    expect(array_column($r['asignaciones'], 'importe'))->toBe(['148.00', '3000.00'])
        ->and($r['despues']['capital_insoluto'])->toBe('9000.00')
        ->and($r['estado']['cargos']['1:ordinario']['pagado'])->toBe('248.00')
        ->and($r['cronograma']['tabla'][0]['numero'])->toBe(2)
        ->and($r['cronograma']['tabla'][0]['interes'])->toBe('168.00');
})->with(['obligacion_vencida', 'saldo_insoluto', 'capital_inicial']);

test('anticipo Bullet liquida el atraso sin consumir el ordinario futuro durante gracia y sustitución', function (string $base, string $gracia, string $intereses) {
    [$inicial, $tabla] = escenarioAnticipoBullet(base: $base, ajustes: ['dias_gracia_mora' => 3, 'politica_mora' => compact('gracia', 'intereses')]);
    $pagos = app(MotorPagos::class);
    $antes = $pagos->avanzar($inicial, '2027-02-03');
    $r = app(MotorAnticipos::class)->calcular($antes, $tabla, 'prepago', '3248');
    expect($r['despues']['capital_insoluto'])->toBe('9000.00')
        ->and($r['estado']['gracia_bullet'])->toBe($antes['gracia_bullet'])
        ->and($r['estado']['cargos']['2:ordinario']['generado'])->toBe($antes['cargos']['2:ordinario']['generado'])
        ->and($r['estado']['cargos']['2:ordinario']['pagado'])->toBe('0.00');
    $despues = $pagos->avanzar($r['estado'], '2027-02-06');
    expect(D::round($despues['cargos']['2:ordinario']['generado']))->toBe('34.00')
        ->and($despues['gracia_bullet'])->toBe([])
        ->and(D::round($despues['cargos']['1:moratorio']['generado']))->toBe('0.00');
})->with(['obligacion_vencida', 'saldo_insoluto', 'capital_inicial'])->with(['efectiva', 'retroactiva'])->with(['ambos', 'sustituye']);

test('anticipo Bullet conserva impuestos del devengado y separa comisión gravada del capital', function (string $politica) {
    [$inicial, $tabla] = escenarioAnticipoBullet($politica, ajustes: ['fiscalidad' => ['ordinario' => ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto']],
        'comisiones' => [['momento_cobro' => 'evento', 'evento_cobro' => 'prepago', 'importe' => '10', 'tipo_importe' => 'porcentaje',
            'base_calculo' => 'capital_anticipado', 'obligatoria' => true, 'concepto' => ['nombre' => 'Anticipo'],
            'fiscalidad' => ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto']]]]);
    $pagos = app(MotorPagos::class);
    $r = app(MotorAnticipos::class)->calcular($pagos->avanzar($inicial, '2027-01-16'), $tabla, 'prepago', '3481.92');
    $clave = ($politica === 'periodicos' ? '1' : '2').':ordinario';
    expect($r['capital_anticipado'])->toBe('3120.00')
        ->and(array_column($r['asignaciones'], 'total'))->toBe(['361.92', '3120.00'])
        ->and($r['estado']['cargos'][$clave]['impuesto_contabilizado'])->toBe('19.20')
        ->and($r['estado']['cargos'][$clave]['impuesto_pagado'])->toBe('0.00');
    $state = $r['estado'];
    foreach ($r['cronograma']['tabla'] as $fila) {
        $state = $pagos->avanzar($state, $fila['fecha']);
        expect($pagos->resumen($state)['exigible'])->toBe($fila['pago_total']);
        if (D::compare($fila['pago_total'], '0') > 0) {
            $state = $pagos->aplicar($state, $fila['pago_total'])['estado'];
        }
    }
})->with(['periodicos', 'al_vencimiento']);

test('liquidación Bullet cubre devengos conservados y detiene el cálculo sin interés futuro', function (string $politica) {
    [$inicial, $tabla] = escenarioAnticipoBullet($politica);
    $pagos = app(MotorPagos::class);
    $anticipos = app(MotorAnticipos::class);
    $antes = $pagos->avanzar($inicial, '2027-01-16');
    $directa = $anticipos->calcular($antes, $tabla, 'liquidacion', null);
    expect($directa['importe'])->toBe('12120.00');
    $anticipo = $anticipos->calcular($antes, $tabla, 'prepago', '3120');
    $r = $anticipos->calcular($pagos->avanzar($anticipo['estado'], '2027-01-20'), $anticipo['cronograma'], 'liquidacion', '9023.68');
    expect($r['despues']['capital_insoluto'])->toBe('0.00')
        ->and($r['despues']['exigible'])->toBe('0.00')
        ->and($r['despues']['estado_operativo'])->toBe('liquidado')
        ->and($r['cronograma']['tabla'])->toBe([])
        ->and($r['estado']['cargos']['2:capital']['fecha'])->toBe('2027-03-01');
    $futuro = $pagos->avanzar($r['estado'], '2027-04-01');
    expect($futuro['cargos'])->toBe($r['estado']['cargos'])
        ->and($pagos->resumen($futuro)['exigible'])->toBe('0.00');
    expect(fn () => $anticipos->calcular($antes, $tabla, 'liquidacion', '12120.01'))->toThrow(ValidationException::class, 'cotización');
})->with(['periodicos', 'al_vencimiento']);

test('Bullet rechaza anticipo total mínimo incompatible evidencia perdida y plazos distintos', function (string $caso) {
    [$state, $tabla] = escenarioAnticipoBullet();
    $state = app(MotorPagos::class)->avanzar($state, '2027-01-16');
    $importe = '3120';
    match ($caso) {
        'total' => $importe = '12000',
        'minimo' => $state['producto']['reglas']['monto_minimo_prepago'] = '4000',
        'plazo' => $state['producto']['reglas']['aplicacion_prepago'] = 'reducir_plazo',
        'motor' => $tabla['motor_calculo_version'] = 1,
        'metodo' => $tabla['metodo'] = 'capital_fijo',
        'numerador' => $state['cargos']['1:ordinario']['generado_numerador'] = null,
        'origen' => $state['capital_inicial'] = null,
        'vencido' => $state = app(MotorPagos::class)->avanzar($state, '2027-03-01'),
    };
    try {
        app(MotorAnticipos::class)->calcular($state, $tabla, 'prepago', $importe);
        $this->fail('La operación incompatible debe rechazarse.');
    } catch (ValidationException $error) {
        expect($error->errors())->toHaveKey('pago')
            ->and($error->getMessage())->not->toContain('validation.');
    }
})->with(['total', 'minimo', 'plazo', 'motor', 'metodo', 'numerador', 'origen', 'vencido']);

test('Bullet conserva fechas bisiestas y centavos al proyectar numeradores sin redondear segmentos', function (string $politica, array $fechas) {
    [$inicial, $tabla] = escenarioAnticipoBullet($politica, ajustes: ['tasa_ordinaria_anual' => '35.99999999',
        'fiscalidad' => ['ordinario' => ['tratamiento' => 'gravado', 'tasa' => '16.12345678', 'base' => 'importe_concepto']]],
        fechas: $fechas, inicio: '2028-01-31');
    $pagos = app(MotorPagos::class);
    $antes = $pagos->avanzar($inicial, '2028-02-02');
    $r = app(MotorAnticipos::class)->calcular($antes, $tabla, 'prepago', '3120.01');
    expect($r['despues']['capital_insoluto'])->toBe('8879.99')
        ->and(array_column($r['cronograma']['tabla'], 'fecha'))->toBe($fechas)
        ->and(array_column($r['cronograma']['tabla'], 'capital'))->toBe(['0.00', '0.00', '8879.99']);
    $state = $r['estado'];
    foreach ($r['cronograma']['tabla'] as $fila) {
        $fecha = $fila['fecha'];
        $entero = $pagos->avanzar($state, $fecha);
        $partido = $pagos->avanzar($pagos->avanzar($state, CarbonImmutable::parse($fecha, 'UTC')->subDay()->toDateString()), $fecha);
        expect($partido)->toBe($entero)
            ->and($pagos->resumen($entero)['exigible'])->toBe($fila['pago_total']);
        $state = D::compare($fila['pago_total'], '0') > 0 ? $pagos->aplicar($entero, $fila['pago_total'])['estado'] : $entero;
    }
    expect($pagos->resumen($state)['capital_insoluto'])->toBe('0.00');
})->with(['periodicos', 'al_vencimiento'])->with([
    [['2028-02-07', '2028-02-14', '2028-02-21']],
    [['2028-02-15', '2028-02-29', '2028-03-15']],
    [['2028-02-29', '2028-03-31', '2028-04-30']],
]);

test('Bullet conserva comisiones futuras al anticipar y solo cobra las aplicables al liquidar', function () {
    [$state, $tabla] = escenarioAnticipoBullet();
    $fiscalidad = ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'];
    foreach ($tabla['tabla'] as &$fila) {
        $fila['comisiones_detalle'] = [['concepto' => 'Administración', 'importe' => '100.00', 'fiscalidad' => $fiscalidad, 'modalidad' => null]];
    }
    unset($fila);
    $state['producto']['comisiones'] = [['momento_cobro' => 'liquidacion', 'evento_cobro' => 'liquidacion_anticipada', 'importe' => '50',
        'tipo_importe' => 'fijo', 'base_calculo' => 'no_aplica', 'obligatoria' => false, 'fiscalidad' => $fiscalidad, 'concepto' => ['nombre' => 'Cierre']]];
    $pagos = app(MotorPagos::class);
    $inicial = $pagos->iniciar($state['producto'], $tabla);
    $antes = $pagos->avanzar($inicial, '2027-01-16');
    $r = app(MotorAnticipos::class)->calcular($antes, $tabla, 'prepago', '3120');
    expect(array_column($r['cronograma']['tabla'], 'comisiones'))->toBe(['100.00', '100.00'])
        ->and($r['estado']['cargos']['1:comision:0'])->toBe($antes['cargos']['1:comision:0']);
    $alDia = $pagos->avanzar($r['estado'], '2027-02-01');
    $liquidacion = app(MotorAnticipos::class)->calcular($alDia, $r['cronograma'], 'liquidacion', null, [0]);
    expect($liquidacion['importe'])->toBe('9268.72')
        ->and(array_column(array_filter($liquidacion['asignaciones'], fn ($a) => $a['tipo'] === 'comision'), 'total'))->toBe(['116.00', '58.00'])
        ->and($liquidacion['estado']['cargos']['2:comision:0']['vigente'])->toBeFalse()
        ->and($liquidacion['despues']['exigible'])->toBe('0.00');
});

test('liquidación vencida Bullet mantiene las tres bases después de un anticipo sin duplicar principal', function (string $politica, string $base, string $mora) {
    [$state, $tabla] = escenarioAnticipoBullet($politica, $base);
    $pagos = app(MotorPagos::class);
    $r = app(MotorAnticipos::class)->calcular($pagos->avanzar($state, '2027-01-16'), $tabla, 'prepago', '3120');
    $antes = $pagos->avanzar($r['estado'], '2027-03-02');
    $liquidacion = app(MotorAnticipos::class)->calcular($antes, $r['cronograma'], 'liquidacion', null);
    expect($liquidacion['evento'])->toBe('liquidacion_ordinaria')
        ->and($liquidacion['importe'])->toBe(D::add('9266.40', $mora, 2))
        ->and($liquidacion['despues']['exigible'])->toBe('0.00')
        ->and($liquidacion['despues']['capital_insoluto'])->toBe('0.00');
})->with([
    // Por obligación: 214.72 × 0.001 × 29 = 6.23; 9045.76 × 0.001 = 9.05.
    ['periodicos', 'obligacion_vencida', '15.28'], ['periodicos', 'saldo_insoluto', '257.52'], ['periodicos', 'capital_inicial', '348.00'],
    ['al_vencimiento', 'obligacion_vencida', '9.26'], ['al_vencimiento', 'saldo_insoluto', '8.88'], ['al_vencimiento', 'capital_inicial', '12.00'],
]);
