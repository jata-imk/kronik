<?php

use App\Enums\ConvencionInteres;
use App\Services\Credito\Convenciones\DiasReales360;
use App\Services\Credito\ConvencionesDias;
use App\Services\Credito\InteresPorPeriodo;
use App\Services\Credito\MotorPagos;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

test('Act360 conserva días civiles reales y base fija incluso con febrero bisiesto y cambio de hora', function (string $desde, string $hasta, string $zona, int $dias, string $interes) {
    $inicio = CarbonImmutable::parse($desde, $zona);
    $fin = CarbonImmutable::parse($hasta, $zona);
    $convencion = new DiasReales360;
    expect($convencion->dias($inicio, $fin))->toBe($dias)
        ->and($convencion->baseAnual())->toBe(360)
        ->and(Decimal::round(app(InteresPorPeriodo::class)->calcular('12000', '24', $inicio, $fin, ConvencionInteres::DiasReales360)))->toBe($interes);
})->with([
    ['2027-02-01', '2027-03-01', 'UTC', 28, '224.00'],
    ['2028-02-01', '2028-03-01', 'UTC', 29, '232.00'],
    ['2027-01-31', '2027-03-31', 'UTC', 59, '472.00'],
    ['2026-03-07', '2026-03-09', 'America/New_York', 2, '16.00'],
    ['2026-11-01', '2026-11-02', 'America/New_York', 1, '8.00'],
]);

test('un periodo sin avance no devenga y uno invertido se rechaza en español', function () {
    $fecha = CarbonImmutable::parse('2028-02-29');
    $convencion = new DiasReales360;
    expect($convencion->dias($fecha, $fecha))->toBe(0);
    expect(fn () => $convencion->dias($fecha, $fecha->subDay()))
        ->toThrow(InvalidArgumentException::class, 'El fin del periodo de devengo no puede ser anterior al inicio.');
});

test('la evidencia histórica sin convención se interpreta sin añadir campos al original', function () {
    $producto = ['reglas' => ['permite_prepago_parcial' => true]];
    $antes = $producto;
    expect(ConvencionInteres::desdeProducto($producto))->toBe(ConvencionInteres::DiasReales360)
        ->and(ConvencionInteres::desdeCronograma([]))->toBe(ConvencionInteres::DiasReales360)
        ->and($producto)->toBe($antes);
});

test('convenciones futuras o marcas inválidas nunca se interpretan como Act360', function (mixed $valor) {
    expect(fn () => ConvencionInteres::desdeReglas(['convencion_interes' => $valor]))
        ->toThrow(ValidationException::class, 'La convención de días de este registro no está soportada.');
    expect(fn () => ConvencionInteres::desdeCronograma(['convencion_interes' => $valor]))
        ->toThrow(ValidationException::class, 'La convención de días de este registro no está soportada.');
})->with([null, false, 365, 'dias_reales_365', '30_360', '30e_360', '']);

test('el catálogo habilitado solo permite resolver Act360', function () {
    expect(ConvencionInteres::cases())->toBe([ConvencionInteres::DiasReales360])
        ->and(app(ConvencionesDias::class)->resolver(ConvencionInteres::DiasReales360))->toBeInstanceOf(DiasReales360::class);
});

test('el avance bloquea convenciones no soportadas antes de interpretar acumuladores existentes', function () {
    expect(fn () => app(MotorPagos::class)->avanzar([
        'producto' => ['reglas' => ['convencion_interes' => 'dias_reales_365']],
        'fecha' => '2026-01-01', 'estado_operativo' => 'liquidado',
    ], '2026-01-02'))->toThrow(ValidationException::class, 'La convención de días de este registro no está soportada.');
});
