<?php

use App\Enums\ConvencionInteres;
use App\Enums\MetodoAmortizacion;
use App\Enums\VersionMotorCredito;
use App\Services\Credito\Amortizacion\AmortizacionInvalida;
use App\Services\Credito\Amortizacion\CalculadorAmortizacion;
use App\Services\Credito\Amortizacion\CondicionesAmortizacion;
use App\Services\Credito\Amortizacion\EstrategiasAmortizacion;
use App\Services\Credito\ConvencionesDias;
use App\Services\Credito\InteresPorPeriodo;
use Carbon\CarbonImmutable;

function calculadorReutilizable(): CalculadorAmortizacion
{
    return new CalculadorAmortizacion(new InteresPorPeriodo(new ConvencionesDias), new EstrategiasAmortizacion);
}

test('el cálculo puro reproduce exactamente las tablas capturadas en 56026d0', function () {
    $casos = json_decode(file_get_contents(__DIR__.'/../Fixtures/amortizacion-56026d0.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($casos as $c) {
        $resultado = calculadorReutilizable()->calcular(new CondicionesAmortizacion($c['principal'], $c['tasa'], CarbonImmutable::parse($c['inicio']),
            array_map(fn ($f) => CarbonImmutable::parse($f), $c['fechas']), MetodoAmortizacion::from($c['metodo']), VersionMotorCredito::from($c['motor']), ConvencionInteres::DiasReales360));
        expect($resultado->toArray())->toBe($c['resultado']);
    }
});

test('simple y disposiciones usan estrategias sin estado ni dependencia de Laravel', function () {
    $calculador = calculadorReutilizable();
    $condiciones = fn ($metodo) => new CondicionesAmortizacion('1000', '36', CarbonImmutable::parse('2026-01-01'),
        [CarbonImmutable::parse('2026-01-31'), CarbonImmutable::parse('2026-03-02')], $metodo, VersionMotorCredito::Act360Racional, ConvencionInteres::DiasReales360);
    $simple = $calculador->calcular($condiciones(MetodoAmortizacion::CapitalFijo));
    $disposicionB = $calculador->calcular($condiciones(MetodoAmortizacion::CuotaNivelada));
    $disposicionA = $calculador->calcular($condiciones(MetodoAmortizacion::CapitalFijo));
    expect($disposicionA->toArray())->toBe($simple->toArray())
        ->and(array_column($simple->tabla, 'capital'))->toBe(['500.00', '500.00'])
        ->and(array_column($simple->tabla, 'interes'))->toBe(['30.00', '15.00'])
        ->and($disposicionB->base->cuotaRedondeada)->toBe('522.61')
        ->and(array_column($disposicionB->tabla, 'capital'))->toBe(['492.61', '507.39'])
        ->and($calculador->calcular($condiciones(MetodoAmortizacion::CuotaNivelada))->toArray())->toBe($disposicionB->toArray());
});

test('los errores del cálculo puro son de dominio', function () {
    $c = new CondicionesAmortizacion('0.06', '0', CarbonImmutable::parse('2026-01-01'),
        array_map(fn ($dia) => CarbonImmutable::parse('2026-01-01')->addDays($dia), range(1, 12)), MetodoAmortizacion::CapitalFijo, VersionMotorCredito::Act360Racional, ConvencionInteres::DiasReales360);
    expect(fn () => calculadorReutilizable()->calcular($c))->toThrow(AmortizacionInvalida::class, 'no permiten amortizaciones positivas');
});
