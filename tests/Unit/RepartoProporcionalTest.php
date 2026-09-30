<?php

use App\Services\Credito\RepartoProporcional;
use App\Support\Decimal;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

test('reparte el abono entre concepto e impuesto sin imponer una tasa', function (string $pago, string $concepto, string $impuesto, array $esperado) {
    expect(app(RepartoProporcional::class)->repartir($pago, $concepto, $impuesto))->toBe($esperado);
})->with([
    ['58.00', '100.00', '16.00', ['concepto' => '50.00', 'impuesto' => '8.00']],
    ['108.00', '100.00', '8.00', ['concepto' => '100.00', 'impuesto' => '8.00']],
    ['58.00', '100.00', '0.00', ['concepto' => '58.00', 'impuesto' => '0.00']],
    ['0.01', '0.01', '0.01', ['concepto' => '0.01', 'impuesto' => '0.00']],
    ['0.01', '0.00', '0.01', ['concepto' => '0.00', 'impuesto' => '0.01']],
    ['0.00', '0.00', '0.00', ['concepto' => '0.00', 'impuesto' => '0.00']],
    ['99999999999999.99', '99999999999999.99', '0.00', ['concepto' => '99999999999999.99', 'impuesto' => '0.00']],
]);

test('parcialidades conservan centavos y nunca exceden saldos individuales', function () {
    $service = app(RepartoProporcional::class);
    for ($c = 0; $c <= 15; $c++) {
        for ($t = 0; $t <= 15; $t++) {
            $concepto = bcdiv((string) $c, '100', 2);
            $impuesto = bcdiv((string) $t, '100', 2);
            for ($p = 0; $p <= $c + $t; $p++) {
                $pago = bcdiv((string) $p, '100', 2);
                $r = $service->repartir($pago, $concepto, $impuesto);
                expect(Decimal::add($r['concepto'], $r['impuesto'], 2))->toBe($pago);
                expect(Decimal::compare($r['concepto'], $concepto))->toBeLessThanOrEqual(0);
                expect(Decimal::compare($r['impuesto'], $impuesto))->toBeLessThanOrEqual(0);
                expect(Decimal::compare($r['concepto'], '0'))->toBeGreaterThanOrEqual(0);
                expect(Decimal::compare($r['impuesto'], '0'))->toBeGreaterThanOrEqual(0);
            }
        }
    }
    $r = $service->repartir('0.01', '0.01', '0.01');
    expect($service->repartir('0.01', Decimal::sub('0.01', $r['concepto'], 2), Decimal::sub('0.01', $r['impuesto'], 2)))
        ->toBe(['concepto' => '0.00', 'impuesto' => '0.01']);
});

test('rechaza excedentes negativos precisión oculta y valores no monetarios', function (string $pago) {
    expect(fn () => app(RepartoProporcional::class)->repartir($pago, '100.00', '16.00'))->toThrow(ValidationException::class);
})->with(['116.01', '-1', '0.001', '1e2', 'NaN', '1,000', '100000000000000.00']);
