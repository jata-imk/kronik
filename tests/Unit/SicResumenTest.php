<?php

use App\Services\SICs\SicResumen;

test('SIC summary separates currencies keeps absent balances unknown and counts only valid dated consultations', function () {
    $r = (new SicResumen)->construir(['secciones' => ['creditos' => [
        ['claveUnidadMonetaria' => 'MX', 'saldoActual' => '100.10', 'saldoVencido' => '10.00', 'tipoCredito' => 'PP'],
        ['claveUnidadMonetaria' => 'US', 'saldoActual' => '50.00', 'saldoVencido' => 0, 'tipoCredito' => 'TC'],
        ['claveUnidadMonetaria' => 'MX', 'saldoActual' => '20.20', 'tipoCredito' => 'PP'],
        ['claveUnidadMonetaria' => 'UD', 'saldoActual' => null, 'saldoVencido' => null],
    ], 'consultas' => [['fechaConsulta' => '2026-02-28'], ['fechaConsulta' => '2026-02-10'], ['fechaConsulta' => '2026-02-30'], []]]]);
    expect($r['monedas'][0]['saldo_actual'])->toBe('120.30')->and($r['monedas'][1]['saldo_actual'])->toBe('50.00')
        ->and($r['monedas'][2]['saldo_actual'])->toBeNull()->and($r['monedas'][0]['cuentas_con_vencido'])->toBe(1)
        ->and($r['atraso'][2]['cantidad'])->toBe(2)->and($r['consultas_meses'])->toBe([['etiqueta' => '2026-02', 'cantidad' => 2]])
        ->and($r['consultas_sin_fecha'])->toBe(2);
});
