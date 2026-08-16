<?php

use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Services\Credito\CalendarioCreditoSimple;
use App\Services\Credito\TablaAmortizacionCreditoSimple;
use Carbon\CarbonImmutable;

test('calendario mantiene intervalos y ancla de fin de mes', function (PeriodicidadCredito $periodicidad, string $inicio, array $esperadas) {
    $fechas = app(CalendarioCreditoSimple::class)->fechas(CarbonImmutable::parse($inicio), $periodicidad, count($esperadas));

    expect(array_map(fn ($fecha) => $fecha->toDateString(), $fechas))->toBe($esperadas);
})->with([
    'semanal' => [PeriodicidadCredito::Semanal, '2026-01-01', ['2026-01-08', '2026-01-15']],
    'quincenal' => [PeriodicidadCredito::Quincenal, '2026-01-01', ['2026-01-16', '2026-01-31']],
    'mensual fin de mes' => [PeriodicidadCredito::Mensual, '2026-01-31', ['2026-02-28', '2026-03-31']],
]);

test('cuota nivelada cierra cada fila a centavos', function () {
    $inicio = CarbonImmutable::parse('2026-08-16');
    $fechas = app(CalendarioCreditoSimple::class)->fechas($inicio, PeriodicidadCredito::Mensual, 12);
    $resultado = app(TablaAmortizacionCreditoSimple::class)->calcular('5000', '36', $inicio, $fechas, MetodoAmortizacion::CuotaNivelada);

    expect($resultado['cuota_redondeada'])->toBe('503.64')
        ->and($resultado['tabla'][0])->toMatchArray([
            'saldo_inicial' => '5000.00', 'capital' => '348.64', 'interes' => '155.00',
            'pago_total' => '503.64', 'saldo_final' => '4651.36',
        ])
        ->and($resultado['tabla'][11]['saldo_final'])->toBe('0.00')
        ->and(array_reduce($resultado['tabla'], fn ($total, $fila) => bcadd($total, $fila['capital'], 2), '0.00'))->toBe('5000.00');
});

test('capital fijo conserva el principal y liquida el residuo', function (PeriodicidadCredito $periodicidad) {
    $inicio = CarbonImmutable::parse('2026-01-15');
    $fechas = app(CalendarioCreditoSimple::class)->fechas($inicio, $periodicidad, 7);
    $resultado = app(TablaAmortizacionCreditoSimple::class)->calcular('1000', '0', $inicio, $fechas, MetodoAmortizacion::CapitalFijo);

    expect($resultado['tabla'][0]['capital'])->toBe('142.86')
        ->and($resultado['tabla'][6]['capital'])->toBe('142.84')
        ->and($resultado['tabla'][6]['saldo_final'])->toBe('0.00');
})->with(PeriodicidadCredito::cases());
