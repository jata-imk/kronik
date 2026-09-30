<?php

use App\Services\Credito\MotorPagos;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

test('varias cuotas conservan capital futuro y aplican primero la deuda más antigua', function () {
    $motor = app(MotorPagos::class);
    $producto = estadoPagoQa('efectiva', 'sustituye')['producto'];
    $state = $motor->iniciar($producto, ['escenario' => ['fecha_disposicion' => '2026-09-01'], 'tabla' => [
        ['numero' => 1, 'fecha' => '2026-09-10', 'capital' => '500.00'],
        ['numero' => 2, 'fecha' => '2026-09-20', 'capital' => '500.00'],
    ]]);
    $state = $motor->avanzar($state, '2026-09-14');
    expect(\App\Support\Decimal::round($state['cargos']['1:ordinario']['generado']))->toBe('10.50')
        ->and(\App\Support\Decimal::round($state['cargos']['2:ordinario']['generado']))->toBe('2.00')
        ->and(\App\Support\Decimal::round($state['cargos']['1:moratorio']['generado']))->toBe('1.00');
    $result = $motor->aplicar($state, '511.50');
    expect($result['resumen']['capital_insoluto'])->toBe('500.00')
        ->and($result['resumen']['exigible'])->toBe('0.00')
        ->and(collect($result['asignaciones'])->pluck('cuota')->unique()->all())->toBe([1]);
});

test('usa días reales en bisiesto y no cobra otra vez comisiones financiadas o retenidas', function () {
    $motor = app(MotorPagos::class);
    $producto = estadoPagoQa()['producto'];
    $comision = ['concepto' => 'Comisión sintética', 'importe' => '100.00', 'fiscalidad' => ['tratamiento' => 'gravado', 'tasa' => '8', 'base' => 'importe_concepto']];
    $state = $motor->iniciar($producto, ['escenario' => ['fecha_disposicion' => '2028-02-28'], 'tabla' => [
        ['numero' => 0, 'fecha' => '2028-02-28', 'capital' => '0', 'comisiones_detalle' => [
            [...$comision, 'modalidad' => 'financiada'], [...$comision, 'modalidad' => 'retenida'], [...$comision, 'modalidad' => 'pago_separado'],
        ]],
        ['numero' => 1, 'fecha' => '2028-03-01', 'capital' => '1000.00'],
    ]]);
    $state = $motor->avanzar($state, '2028-03-01');
    expect($motor->resumen($state)['exigible'])->toBe('1110.00');
    $result = $motor->aplicar($state, '54.00');
    expect($result['asignaciones'][0]['importe'])->toBe('50.00')->and($result['asignaciones'][0]['impuesto'])->toBe('4.00');
});

function estadoPagoQa(string $gracia = 'efectiva', string $intereses = 'ambos'): array
{
    return app(MotorPagos::class)->iniciar([
        'politica_mora' => compact('gracia', 'intereses'), 'dias_gracia_mora' => 3,
        'tasa_ordinaria_anual' => '36', 'tasa_moratoria_anual' => '72',
        'fiscalidad' => ['ordinario' => ['tratamiento' => 'exento'], 'moratorio' => ['tratamiento' => 'exento']],
    ], ['escenario' => ['fecha_disposicion' => '2026-09-01'], 'tabla' => [
        ['numero' => 1, 'fecha' => '2026-09-10', 'capital' => '1000.00', 'comisiones_detalle' => []],
    ]]);
}

test('devenga sobre capital real y distingue las combinaciones aprobadas', function (string $gracia, string $intereses, string $ordinario, string $mora) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoPagoQa($gracia, $intereses), '2026-09-14');
    $filas = collect($motor->resumen($state)['conceptos'])->keyBy('tipo');
    expect($filas['ordinario']['importe'])->toBe($ordinario)->and($filas['moratorio']['importe'])->toBe($mora);
})->with([
    ['efectiva', 'ambos', '13.00', '2.00'],
    ['efectiva', 'sustituye', '12.00', '2.00'],
    ['retroactiva', 'ambos', '13.00', '8.00'],
    ['retroactiva', 'sustituye', '9.00', '8.00'],
]);

test('el capital pagado deja de generar interés y la consulta no redondea cada día', function () {
    $motor = app(MotorPagos::class);
    $inicial = estadoPagoQa();
    $state = $motor->avanzar($inicial, '2026-09-10');
    $resultado = $motor->aplicar($state, '509.00');
    expect($resultado['resumen']['capital_insoluto'])->toBe('500.00');
    $fin = $motor->avanzar($resultado['estado'], '2026-09-12');
    expect(collect($motor->resumen($fin)['conceptos'])->keyBy('tipo')['ordinario']['importe'])->toBe('1.00');
    expect($motor->avanzar($motor->avanzar($inicial, '2026-09-06'), '2026-09-14'))->toBe($motor->avanzar($inicial, '2026-09-14'));
});

test('bloquea la parcialidad B con sustitución antes de devolver instrucciones de registro', function (string $fecha) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoPagoQa('retroactiva', 'sustituye'), '2026-09-10');
    $state = $motor->aplicar($state, '9.00')['estado']; // Ordinary through due date.
    $state = $motor->avanzar($state, $fecha);
    $antes = $state;
    expect(fn () => $motor->aplicar($state, '1.00'))->toThrow(ValidationException::class, 'No se registró el pago parcial');
    expect($state)->toBe($antes);
    expect($motor->resumen($motor->avanzar($state, '2026-09-14'))['exigible'])->toBe('1008.00');
})->with(['2026-09-11', '2026-09-12', '2026-09-13']);

test('admite cubrir la cuota completa dentro de gracia B sin generar mora posterior', function () {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoPagoQa('retroactiva', 'sustituye'), '2026-09-12');
    $resultado = $motor->aplicar($state, '1011.00');
    expect($resultado['resumen']['exigible'])->toBe('0.00');
    expect($motor->resumen($motor->avanzar($resultado['estado'], '2026-09-20'))['exigible'])->toBe('0.00');
});

test('B con sustitución permite pagar solo una cuota anterior sin tocar la cuota en gracia', function () {
    $motor = app(MotorPagos::class);
    $state = $motor->iniciar(estadoPagoQa('retroactiva', 'sustituye')['producto'], [
        'escenario' => ['fecha_disposicion' => '2026-09-01'], 'tabla' => [
            ['numero' => 1, 'fecha' => '2026-09-10', 'capital' => '500.00'],
            ['numero' => 2, 'fecha' => '2026-09-20', 'capital' => '500.00'],
        ],
    ]);
    $state = $motor->avanzar($state, '2026-09-21');
    $result = $motor->aplicar($state, '1.00');
    expect($result['asignaciones'][0]['cuota'])->toBe(1)
        ->and($result['estado']['cuotas'][2]['capital'])->toBe('500.00');
});

test('el límite no bloquea otras modalidades ni fechas fuera de gracia', function (string $gracia, string $intereses, string $fecha) {
    $motor = app(MotorPagos::class);
    $state = $motor->avanzar(estadoPagoQa($gracia, $intereses), $fecha);
    expect($motor->aplicar($state, '1.00')['asignaciones'])->toHaveCount(1);
})->with([
    ['efectiva', 'ambos', '2026-09-12'], ['efectiva', 'sustituye', '2026-09-12'],
    ['retroactiva', 'ambos', '2026-09-12'], ['retroactiva', 'sustituye', '2026-09-10'],
    ['retroactiva', 'sustituye', '2026-09-14'],
]);
