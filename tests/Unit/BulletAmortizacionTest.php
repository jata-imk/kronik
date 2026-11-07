<?php

use App\Enums\ConvencionInteres;
use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Enums\PoliticaInteresBullet;
use App\Enums\VersionMotorCredito;
use App\Services\Credito\Amortizacion\AmortizacionInvalida;
use App\Services\Credito\Amortizacion\CalculadorAmortizacion;
use App\Services\Credito\Amortizacion\CondicionesAmortizacion;
use App\Services\Credito\Amortizacion\EstrategiasAmortizacion;
use App\Services\Credito\Amortizacion\ExigibilidadInteresBullet;
use App\Services\Credito\CalendarioCreditoSimple;
use App\Services\Credito\CatalogoCapacidadesCredito;
use App\Services\Credito\ConvencionesDias;
use App\Services\Credito\InteresPorPeriodo;
use Carbon\CarbonImmutable;

function bulletPuro(string $capital = '12000', string $tasa = '24', ?array $fechas = null, string $inicio = '2027-01-01', VersionMotorCredito $motor = VersionMotorCredito::Act360Racional)
{
    $intereses = new InteresPorPeriodo(new ConvencionesDias);
    $resultado = (new CalculadorAmortizacion($intereses, new EstrategiasAmortizacion))->calcular(
        new CondicionesAmortizacion($capital, $tasa, CarbonImmutable::parse($inicio),
            array_map(fn ($fecha) => CarbonImmutable::parse($fecha), $fechas ?? ['2027-02-01', '2027-03-01']),
            MetodoAmortizacion::Bullet, $motor, ConvencionInteres::DiasReales360));

    return [$resultado, new ExigibilidadInteresBullet($intereses)];
}

test('Bullet separa capital al vencimiento y ambas políticas de interés', function (PoliticaInteresBullet $politica, array $intereses, array $pagos) {
    [$base, $exigibilidad] = bulletPuro();
    $resultado = $exigibilidad->aplicar($base, $politica, ConvencionInteres::DiasReales360);
    expect(array_column($resultado->tabla, 'capital'))->toBe(['0.00', '12000.00'])
        ->and(array_column($resultado->tabla, 'saldo_final'))->toBe(['12000.00', '0.00'])
        ->and(array_column($resultado->tabla, 'interes'))->toBe($intereses)
        ->and(array_column($resultado->tabla, 'pago_cat'))->toBe($pagos)
        ->and($base->tabla[0]['interes'])->toBe('248.00');
})->with([
    [PoliticaInteresBullet::Periodicos, ['248.00', '224.00'], ['248.00', '12224.00']],
    [PoliticaInteresBullet::AlVencimiento, ['0.00', '472.00'], ['0.00', '12472.00']],
]);

test('al vencimiento acumula numeradores antes de redondear y conserva intervalos sin pago', function () {
    [$base, $exigibilidad] = bulletPuro('1', '180', ['2027-01-02', '2027-01-03']);
    $resultado = $exigibilidad->aplicar($base, PoliticaInteresBullet::AlVencimiento, ConvencionInteres::DiasReales360);
    expect(array_column($base->tabla, 'interes'))->toBe(['0.01', '0.01'])
        ->and(array_column($resultado->tabla, 'interes'))->toBe(['0.00', '0.01'])
        ->and(array_column($resultado->tabla, 'pago_cat'))->toBe(['0.00', '1.01']);
});

test('Bullet conserva el calendario vigente en todas las frecuencias', function (PeriodicidadCredito $periodicidad, string $inicio, array $esperadas) {
    $fechas = (new CalendarioCreditoSimple)->fechas(CarbonImmutable::parse($inicio), $periodicidad, 2);
    [$base] = bulletPuro(fechas: array_map(fn ($f) => $f->toDateString(), $fechas), inicio: $inicio);
    expect(array_column($base->tabla, 'fecha'))->toBe($esperadas)
        ->and(array_column($base->tabla, 'capital'))->toBe(['0.00', '12000.00']);
})->with([
    [PeriodicidadCredito::Semanal, '2027-01-01', ['2027-01-08', '2027-01-15']],
    [PeriodicidadCredito::Quincenal, '2027-01-01', ['2027-01-16', '2027-01-31']],
    [PeriodicidadCredito::Mensual, '2028-01-31', ['2028-02-29', '2028-03-31']],
    [PeriodicidadCredito::Mensual, '2027-01-30', ['2027-02-28', '2027-03-30']],
]);

test('Bullet rechaza entradas inválidas y no se aplica al motor histórico', function (string $capital, string $tasa, array $fechas, VersionMotorCredito $motor) {
    expect(fn () => bulletPuro($capital, $tasa, $fechas, motor: $motor))->toThrow(AmortizacionInvalida::class);
})->with([
    ['0', '24', ['2027-02-01'], VersionMotorCredito::Act360Racional],
    ['12000', '-1', ['2027-02-01'], VersionMotorCredito::Act360Racional],
    ['12000', '24', [], VersionMotorCredito::Act360Racional],
    ['12000', '24', ['2027-02-01', '2027-02-01'], VersionMotorCredito::Act360Racional],
    ['12000', '24', ['2027-01-01'], VersionMotorCredito::Act360Racional],
    ['12000', '24', ['2027-02-01'], VersionMotorCredito::Historico],
]);

test('Bullet admite un centavo sin interés sin relajar las guardas de los otros métodos', function () {
    [$base] = bulletPuro('0.01', '0');
    expect(array_column($base->tabla, 'capital'))->toBe(['0.00', '0.01']);
    foreach ([MetodoAmortizacion::CapitalFijo, MetodoAmortizacion::CuotaNivelada] as $metodo) {
        expect(fn () => (new CalculadorAmortizacion(new InteresPorPeriodo(new ConvencionesDias), new EstrategiasAmortizacion))->calcular(
            new CondicionesAmortizacion('0.01', '0', CarbonImmutable::parse('2027-01-01'),
                [CarbonImmutable::parse('2027-02-01'), CarbonImmutable::parse('2027-03-01'), CarbonImmutable::parse('2027-04-01')],
                $metodo, VersionMotorCredito::Act360Racional, ConvencionInteres::DiasReales360)))->toThrow(AmortizacionInvalida::class);
    }
});

test('Bullet operativo exige el motor racional y conserva la exclusión histórica', function () {
    expect(CatalogoCapacidadesCredito::valores('metodos_amortizacion', VersionMotorCredito::Historico))->not->toContain('bullet')
        ->and(CatalogoCapacidadesCredito::valores('metodos_amortizacion', VersionMotorCredito::Act360Racional))->toContain('bullet');
});
