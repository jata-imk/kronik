<?php

use App\Enums\PeriodicidadCredito;
use App\Services\Credito\CatInformativoService;

test('calcula el ejemplo oficial de CAT de Banco de México', function () {
    $pagos = array_fill(0, 24, '962.33');

    $cat = app(CatInformativoService::class)->calcular('14900.00', $pagos, PeriodicidadCredito::Mensual);

    expect($cat)->toBe('57.4');
});

test('el CAT usa la frecuencia anual de cada periodicidad', function () {
    $service = app(CatInformativoService::class);

    expect($service->calcular('1000', array_fill(0, 12, '100'), PeriodicidadCredito::Mensual))
        ->not->toBe($service->calcular('1000', array_fill(0, 12, '100'), PeriodicidadCredito::Semanal));
});
