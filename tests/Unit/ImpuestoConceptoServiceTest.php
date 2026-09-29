<?php

use App\Services\Credito\ImpuestoConceptoService;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

test('proyecta impuesto decimal sobre el concepto y redondea half up', function (string $base, string $tasa, string $esperado) {
    $detalle = app(ImpuestoConceptoService::class)->calcular(['tratamiento' => 'gravado', 'tasa' => $tasa, 'base' => 'importe_concepto'], $base, 'Interés ordinario');
    expect($detalle['impuesto'])->toBe($esperado)->and($detalle['importe_concepto'])->toBe($base);
})->with([['100.00', '16', '16.00'], ['0.05', '10', '0.01'], ['100.00', '16.12345678', '16.12'], ['100.00', '0', '0.00']]);

test('mantiene distintos los tratamientos sin cobro de impuesto', function (string $tratamiento) {
    $detalle = app(ImpuestoConceptoService::class)->calcular(['tratamiento' => $tratamiento], '100.00', 'Comisión');
    expect($detalle['impuesto'])->toBe('0.00')->and($detalle['tratamiento'])->toBe($tratamiento)->and($detalle['tasa'])->toBeNull();
})->with(['exento', 'no_causa']);

test('rechaza fiscalidad ausente o inconsistente incluso para un importe cero', function (?array $politica) {
    expect(fn () => app(ImpuestoConceptoService::class)->calcular($politica, '0.00', 'Interés moratorio'))->toThrow(ValidationException::class);
})->with([
    [null], [['tratamiento' => 'no_definido']],
    [['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'monto_credito']],
    [['tratamiento' => 'gravado', 'base' => 'importe_concepto']],
    [['tratamiento' => 'exento', 'tasa' => '0']],
]);
