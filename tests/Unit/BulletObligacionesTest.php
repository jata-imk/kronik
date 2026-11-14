<?php

use App\Services\Credito\MotorAnticipos;
use App\Services\Credito\MotorPagos;
use App\Support\Decimal as D;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

function estadoObligacionesBullet(string $politica = 'periodicos', string $base = 'obligacion_vencida', string $gracia = 'efectiva', string $intereses = 'ambos', int $dias = 0, array $fechas = ['2027-02-01', '2027-03-01']): array
{
    $producto = ['motor_calculo_version' => 2, 'politica_mora' => compact('gracia', 'intereses'), 'dias_gracia_mora' => $dias,
        'tasa_ordinaria_anual' => '24', 'tasa_moratoria_anual' => '36',
        'reglas' => ['metodos_amortizacion' => ['bullet'], 'base_ordinaria' => 'saldo_insoluto', 'base_moratoria' => $base,
            'politica_interes_bullet' => $politica, 'aplicacion_prepago' => 'conservar_fecha'],
        'fiscalidad' => ['ordinario' => ['tratamiento' => 'no_causa'], 'moratorio' => ['tratamiento' => 'no_causa']]];
    $tabla = ['metodo' => 'bullet', 'motor_calculo_version' => 2, 'escenario' => ['fecha_disposicion' => '2027-01-01'],
        'tabla' => array_map(fn ($i, $fecha) => ['numero' => $i + 1, 'fecha' => $fecha,
            'capital' => $i === array_key_last($fechas) ? '12000.00' : '0.00'], array_keys($fechas), $fechas)];

    return app(MotorPagos::class)->iniciar($producto, $tabla);
}

function moraObligacionesBullet(array $state): string
{
    return D::round(array_reduce($state['cargos'], fn ($s, $c) => $c['tipo'] === 'moratorio' ? D::add($s, $c['generado']) : $s, '0'));
}

test('Bullet cobra ambas políticas en sus fechas y conserva capital íntegro hasta el final', function (string $politica, string $primero, string $final) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoObligacionesBullet($politica), '2027-02-01');
    expect($motor->resumen($state)['exigible'])->toBe($primero)
        ->and($motor->resumen($state)['capital_vencido'])->toBe('0.00')
        ->and(moraObligacionesBullet($state))->toBe('0.00');
    if ($primero !== '0.00') {
        $state = $motor->aplicar($state, $primero)['estado'];
    } else {
        expect(fn () => $motor->aplicar($state, '100'))->toThrow(ValidationException::class, 'excede las obligaciones');
    }
    $state = $motor->avanzar($state, '2027-03-01');
    expect($motor->resumen($state)['exigible'])->toBe($final)
        ->and(moraObligacionesBullet($state))->toBe('0.00');
    $pagado = $motor->aplicar($state, $final)['estado'];
    expect($motor->resumen($motor->avanzar($pagado, '2027-04-01'))['exigible'])->toBe('0.00');
})->with([['periodicos', '248.00', '12224.00'], ['al_vencimiento', '0.00', '12472.00']]);

test('interés vencido dispara las tres bases sin vencer ni amortizar el principal', function (string $base, string $mora) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoObligacionesBullet(base: $base), '2027-02-01');
    $state = $motor->aplicar($state, '100')['estado'];
    $state = $motor->avanzar($state, '2027-02-02');
    $resumen = $motor->resumen($state);
    expect($resumen['capital_insoluto'])->toBe('12000.00')
        ->and($resumen['capital_vencido'])->toBe('0.00')
        ->and($resumen['interes_vencido'])->toBe('148.00')
        ->and($resumen['obligacion_vencida'])->toBe('148.00')
        ->and($resumen['dias_atraso'])->toBe(1)
        ->and(moraObligacionesBullet($state))->toBe($mora);
})->with([['obligacion_vencida', '0.15'], ['saldo_insoluto', '12.00'], ['capital_inicial', '12.00']]);

test('al vencimiento no activa mora por devengado no exigible', function (string $base) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoObligacionesBullet('al_vencimiento', $base), '2027-02-28');
    expect($motor->resumen($state)['exigible'])->toBe('0.00')
        ->and($motor->resumen($state)['dias_atraso'])->toBe(0)
        ->and(moraObligacionesBullet($state))->toBe('0.00')
        ->and(D::round($state['cargos']['2:ordinario']['generado']))->toBe('464.00');
})->with(['obligacion_vencida', 'saldo_insoluto', 'capital_inicial']);

test('mora Bullet conserva las cuatro combinaciones de gracia y sustitución sin borrar el interés original', function (string $gracia, string $intereses, string $ordinario, string $mora) {
    $motor = app(MotorPagos::class);
    $inicial = estadoObligacionesBullet(base: 'saldo_insoluto', gracia: $gracia, intereses: $intereses, dias: 3);
    $state = $motor->avanzar($inicial, '2027-02-05');
    expect(D::round($state['cargos']['1:ordinario']['generado']))->toBe('248.00')
        ->and(D::round($state['cargos']['2:ordinario']['generado']))->toBe($ordinario)
        ->and(moraObligacionesBullet($state))->toBe($mora)
        ->and($motor->avanzar($motor->avanzar($inicial, '2027-02-03'), '2027-02-05'))->toBe($state);
})->with([
    ['efectiva', 'ambos', '32.00', '12.00'], ['efectiva', 'sustituye', '24.00', '12.00'],
    ['retroactiva', 'ambos', '32.00', '48.00'], ['retroactiva', 'sustituye', '0.00', '48.00'],
]);

test('pagar la obligación durante gracia retroactiva descarta la mora diferida y conserva devengo', function (string $base) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoObligacionesBullet(base: $base, gracia: 'retroactiva', intereses: 'sustituye', dias: 3), '2027-02-03');
    $antes = $state;
    expect(fn () => $motor->aplicar($state, '100'))->toThrow(ValidationException::class, 'No se registró el pago parcial')
        ->and($state)->toBe($antes);
    $pagado = $motor->aplicar($state, '248')['estado'];
    $fin = $motor->avanzar($pagado, '2027-02-05');
    expect(moraObligacionesBullet($fin))->toBe('0.00')
        ->and(D::round($fin['cargos']['2:ordinario']['generado']))->toBe('32.00')
        ->and($fin['gracia_bullet'])->toBe([])
        ->and($motor->resumen($fin)['dias_atraso'])->toBe(0);
})->with(['obligacion_vencida', 'saldo_insoluto', 'capital_inicial']);

test('un pago posterior elimina el disparador y la mora acumulada no genera más mora', function (string $base, string $mora) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoObligacionesBullet(base: $base), '2027-02-02');
    $state = $motor->aplicar($state, $motor->resumen($state)['exigible'])['estado'];
    expect($motor->resumen($state)['interes_vencido'])->toBe('0.00');
    $fin = $motor->avanzar($state, '2027-02-10');
    expect(moraObligacionesBullet($fin))->toBe($mora)
        ->and($motor->resumen($fin)['dias_atraso'])->toBe(0)
        ->and($motor->resumen($fin)['capital_insoluto'])->toBe('12000.00');
})->with([['obligacion_vencida', '0.25'], ['saldo_insoluto', '12.00'], ['capital_inicial', '12.00']]);

test('varias obligaciones vencidas no multiplican bases globales y el remanente excluye mora', function (string $base, string $incremento) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoObligacionesBullet(base: $base), '2027-03-01');
    $antes = array_reduce($state['cargos'], fn ($s, $c) => $c['tipo'] === 'moratorio' ? D::add($s, $c['generado']) : $s, '0');
    $fin = $motor->avanzar($state, '2027-03-02');
    $despues = array_reduce($fin['cargos'], fn ($s, $c) => $c['tipo'] === 'moratorio' ? D::add($s, $c['generado']) : $s, '0');
    // Base al iniciar el 02/03: 248 + 224 + 12000 = 12472; sin incluir mora previa ni el ordinario del 02/03.
    expect(D::round(D::sub($despues, $antes)))->toBe($incremento)
        ->and($motor->resumen($fin)['capital_vencido'])->toBe('12000.00')
        ->and($motor->resumen($fin)['dias_atraso'])->toBe(29);
})->with([['obligacion_vencida', '12.47'], ['saldo_insoluto', '12.00'], ['capital_inicial', '12.00']]);

test('el capital inicial permanece fijo después de un pago de capital vencido', function (string $base, string $incremento) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoObligacionesBullet(base: $base), '2027-02-01');
    $state = $motor->aplicar($state, '248')['estado'];
    $state = $motor->avanzar($state, '2027-03-01');
    $state = $motor->aplicar($state, '3224')['estado'];
    $fin = $motor->avanzar($state, '2027-03-02');
    expect($fin['capital_inicial'])->toBe('12000.00')
        ->and($motor->resumen($fin)['capital_insoluto'])->toBe('9000.00')
        ->and(moraObligacionesBullet($fin))->toBe($incremento);
})->with([['obligacion_vencida', '9.00'], ['saldo_insoluto', '9.00'], ['capital_inicial', '12.00']]);

test('el avance Bullet conserva numeradores y estado idéntico por intervalos', function (string $base, string $gracia, string $intereses) {
    $motor = app(MotorPagos::class);
    $inicial = estadoObligacionesBullet(base: $base, gracia: $gracia, intereses: $intereses, dias: 3);
    $entero = $motor->avanzar($inicial, '2027-03-10');
    $parcial = $inicial;
    foreach (['2027-01-16', '2027-02-01', '2027-02-03', '2027-02-05', '2027-03-02', '2027-03-10'] as $fecha) {
        $parcial = $motor->avanzar($parcial, $fecha);
    }
    expect($parcial)->toBe($entero)
        ->and($motor->avanzar($entero, '2027-03-10'))->toBe($entero);
    unset($entero['cargos']['1:moratorio']['generado_numerador']);
    expect(fn () => $motor->avanzar($entero, '2027-03-11'))->toThrow(ValidationException::class, 'acumulador');
})->with(['obligacion_vencida', 'saldo_insoluto', 'capital_inicial'])->with(['efectiva', 'retroactiva'])->with(['ambos', 'sustituye']);

test('Bullet rechaza condiciones incompletas y calendarios incompatibles sin defaults', function (string $caso) {
    $state = estadoObligacionesBullet();
    $producto = $state['producto'];
    $tabla = ['metodo' => 'bullet', 'motor_calculo_version' => 2, 'escenario' => ['fecha_disposicion' => '2027-01-01'], 'tabla' => [
        ['numero' => 1, 'fecha' => '2027-02-01', 'capital' => '0.00'], ['numero' => 2, 'fecha' => '2027-03-01', 'capital' => '12000.00']]];
    match ($caso) {
        'politica' => $producto['reglas']['politica_interes_bullet'] = null,
        'base' => $producto['reglas']['base_moratoria'] = 'capital_vencido',
        'ordinario' => $producto['reglas']['base_ordinaria'] = 'capital_inicial',
        'motor' => [$producto['motor_calculo_version'], $tabla['motor_calculo_version']] = [1, 1],
        'metodo' => $tabla['metodo'] = 'capital_fijo',
        'intermedio' => $tabla['tabla'][0]['capital'] = '1.00',
        'fechas' => $tabla['tabla'][1]['fecha'] = '2027-02-01',
    };
    try {
        app(MotorPagos::class)->iniciar($producto, $tabla);
        $this->fail('Debe rechazar la evidencia incompatible.');
    } catch (ValidationException $error) {
        expect($error->errors())->toHaveKey('pago')
            ->and($error->getMessage())->not->toContain('validation.');
    }
})->with(['politica', 'base', 'ordinario', 'motor', 'metodo', 'intermedio', 'fechas']);

test('Bullet no reconstruye estado perdido ni opera anticipos con calendario incompleto', function () {
    $motor = app(MotorPagos::class);
    $state = estadoObligacionesBullet();
    $sinOrigen = $state;
    unset($sinOrigen['capital_inicial']);
    expect(fn () => $motor->avanzar($sinOrigen, '2027-01-02'))->toThrow(ValidationException::class, 'evidencia Bullet');
    foreach (['prepago', 'liquidacion'] as $tipo) {
        expect(fn () => app(MotorAnticipos::class)->calcular($state, ['metodo' => 'bullet'], $tipo, '3120'))
            ->toThrow(ValidationException::class, 'evidencia Bullet');
    }
});

test('gracia retroactiva superpuesta no cobra mora sobre interés que la sustitución elimina', function (string $base, string $mora) {
    $motor = app(MotorPagos::class);
    $state = estadoObligacionesBullet(base: $base, gracia: 'retroactiva', intereses: 'sustituye', dias: 10,
        fechas: ['2027-01-08', '2027-01-15', '2027-01-22']);
    $state = $motor->avanzar($state, '2027-01-19');
    // 56 exigibles el 08/01. Se sustituyen los 80 ordinarios del 09 al 18/01, incluso los que vencían el 15/01.
    expect(D::round($state['cargos']['1:ordinario']['generado']))->toBe('56.00')
        ->and(D::round($state['cargos']['2:ordinario']['generado']))->toBe('0.00')
        ->and(D::round($state['cargos']['3:ordinario']['generado']))->toBe('0.00')
        ->and(moraObligacionesBullet($state))->toBe($mora)
        ->and($motor->resumen($state)['interes_vencido'])->toBe('56.00');
    // Al salir de gracia el capital final, solo entra 12000; ningún ordinario provisional eliminado.
    $fin = $motor->avanzar($state, '2027-02-02');
    expect(moraObligacionesBullet($fin))->toBe($base === 'obligacion_vencida' ? '133.40' : '300.00');
})->with([['obligacion_vencida', '0.62'], ['saldo_insoluto', '132.00'], ['capital_inicial', '132.00']]);

test('un pago en gracia conserva la base real de cada día anterior y posterior al pago', function (string $base, string $mora) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoObligacionesBullet(base: $base, gracia: 'retroactiva', dias: 3), '2027-02-03');
    $state = $motor->aplicar($state, '100')['estado'];
    $fin = $motor->avanzar($state, '2027-02-05');
    // 2 días sobre 248, otros 2 sobre 148: 0.792, sin recalcular días anteriores desde el remanente actual.
    expect(moraObligacionesBullet($fin))->toBe($mora)
        ->and($motor->resumen($fin)['interes_vencido'])->toBe('148.00');
})->with([['obligacion_vencida', '0.79'], ['saldo_insoluto', '48.00'], ['capital_inicial', '48.00']]);

test('impuestos y comisiones conservan su distribución sin engrosar la base moratoria', function () {
    $motor = app(MotorPagos::class);
    $state = estadoObligacionesBullet();
    foreach ($state['cargos'] as &$cargo) {
        if (in_array($cargo['tipo'], ['ordinario', 'moratorio'], true)) {
            $cargo['fiscalidad'] = ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'];
        }
    }
    unset($cargo);
    $state = $motor->avanzar($state, '2027-02-01');
    $pagado = $motor->aplicar($state, '116');
    expect($pagado['asignaciones'][0]['importe'])->toBe('100.00')
        ->and($pagado['asignaciones'][0]['impuesto'])->toBe('16.00');
    $fin = $motor->avanzar($pagado['estado'], '2027-02-02');
    $resumen = $motor->resumen($fin);
    expect($resumen['obligacion_vencida'])->toBe('148.00')
        ->and($resumen['exigible'])->toBe('171.85')
        ->and(moraObligacionesBullet($fin))->toBe('0.15');
    $comision = estadoObligacionesBullet();
    $comision['producto']['tasa_ordinaria_anual'] = '0';
    $comision['cargos']['1:comision'] = [...$comision['cargos']['1:ordinario'], 'tipo' => 'comision', 'concepto' => 'Comisión sintética', 'generado' => '100'];
    $comision = $motor->avanzar($comision, '2027-02-10');
    expect(moraObligacionesBullet($comision))->toBe('0.00')
        ->and($motor->resumen($comision)['exigible'])->toBe('100.00');
});

test('un residuo exclusivo de impuesto no dispara mora ni sustituye el ordinario Bullet', function (string $base, string $intereses) {
    $motor = app(MotorPagos::class);
    $state = estadoObligacionesBullet(base: $base, intereses: $intereses);
    $state['producto']['tasa_ordinaria_anual'] = '0.004';
    foreach ($state['cargos'] as &$cargo) {
        if ($cargo['tipo'] === 'ordinario') {
            $cargo['fiscalidad'] = ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'];
        }
    }
    unset($cargo);
    // Enero: 0.04 de interés + 0.01 de impuesto. El reparto proporcional permite cuatro abonos al concepto.
    $state = $motor->avanzar($state, '2027-02-01');
    for ($i = 0; $i < 4; $i++) {
        $state = $motor->aplicar($state, '0.01')['estado'];
    }
    expect($motor->resumen($state)['exigible'])->toBe('0.01');
    $state = $motor->avanzar($state, '2027-02-02');
    expect($motor->resumen($state)['interes_vencido'])->toBe('0.00')
        ->and($motor->resumen($state)['obligacion_vencida'])->toBe('0.00')
        ->and($motor->resumen($state)['dias_atraso'])->toBe(0)
        ->and($motor->resumen($state)['exigible'])->toBe('0.01')
        ->and(moraObligacionesBullet($state))->toBe('0.00')
        ->and(D::compare($state['cargos']['2:ordinario']['generado_numerador'], '48'))->toBe(0);
    expect($motor->aplicar($state, '0.01')['resumen']['exigible'])->toBe('0.00');
})->with(['obligacion_vencida', 'saldo_insoluto', 'capital_inicial'])->with(['ambos', 'sustituye']);
