<?php

use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Enums\VersionMotorCredito;
use App\Services\Credito\CalendarioCreditoSimple;
use App\Services\Credito\InteresAct360;
use App\Services\Credito\MotorPagos;
use App\Services\Credito\TablaAmortizacionCreditoSimple;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

function productoMotorRacional(string $tasa = '9', string $gracia = 'efectiva', string $intereses = 'ambos'): array
{
    return ['motor_calculo_version' => 2, 'politica_mora' => compact('gracia', 'intereses'),
        'dias_gracia_mora' => 3, 'tasa_ordinaria_anual' => $tasa, 'tasa_moratoria_anual' => '72',
        'fiscalidad' => ['ordinario' => ['tratamiento' => 'exento'], 'moratorio' => ['tratamiento' => 'exento']]];
}

test('el motor corregido conserva el medio centavo entre contrato devengo y pago', function (MetodoAmortizacion $metodo) {
    $inicio = CarbonImmutable::parse('2026-01-01');
    $tabla = app(TablaAmortizacionCreditoSimple::class)->calcular('100', '9', $inicio, [$inicio->addDays(7)], $metodo, VersionMotorCredito::Act360Racional);
    // 100 × 9 × 7 / 36000 = 0.175; half-up = 0.18.
    expect($tabla['tabla'][0]['interes'])->toBe('0.18')->and($tabla['tabla'][0]['pago_total'])->toBe('100.18');
    $motor = app(MotorPagos::class);
    $estado = $motor->iniciar(productoMotorRacional(), ['motor_calculo_version' => 2,
        'escenario' => ['fecha_disposicion' => '2026-01-01'], 'tabla' => $tabla['tabla']]);
    $estado = $motor->avanzar($estado, '2026-01-08');
    expect($motor->resumen($estado)['exigible'])->toBe('100.18');
    $pagado = $motor->aplicar($estado, '100.18')['estado'];
    expect($motor->resumen($pagado)['capital_insoluto'])->toBe('0.00')
        ->and($motor->resumen($motor->avanzar($pagado, '2026-01-20'))['exigible'])->toBe('0.00');
})->with(MetodoAmortizacion::cases());

test('la evidencia sin versión conserva la proyección histórica sin reescribirla', function () {
    $inicio = CarbonImmutable::parse('2026-01-01');
    $tabla = app(TablaAmortizacionCreditoSimple::class)->calcular('100', '9', $inicio, [$inicio->addDays(7)], MetodoAmortizacion::CapitalFijo);
    expect(VersionMotorCredito::desdeSnapshot([]))->toBe(VersionMotorCredito::Historico)
        ->and($tabla['tabla'][0]['interes'])->toBe('0.17');
});

test('conserva numeradores completos en lugar de sumar cocientes diarios truncados', function () {
    $calculo = app(InteresAct360::class);
    $numerador = '0';
    for ($dia = 0; $dia < 9; $dia++) {
        $numerador = Decimal::add($numerador, $calculo->numerador('1', '20', 1));
    }
    // 1 × 20 × 9 / 36000 = 0.005. Sumar nueve cocientes truncados pierde ese borde.
    expect(Decimal::round($calculo->desdeNumerador($numerador)))->toBe('0.01')
        ->and(Decimal::round($calculo->calcular('1', '20', 9)))->toBe('0.01');
    $motor = app(MotorPagos::class);
    $estado = $motor->iniciar(productoMotorRacional('20'), ['motor_calculo_version' => 2,
        'escenario' => ['fecha_disposicion' => '2026-01-01'], 'tabla' => [
            ['numero' => 1, 'fecha' => '2026-01-10', 'capital' => '1.00'],
        ]]);
    expect($motor->resumen($motor->avanzar($estado, '2026-01-10'))['exigible'])->toBe('1.01');
});

test('el avance racional por intervalos conserva el mismo estado y política de gracia', function (string $gracia, string $intereses, string $ordinario, string $mora) {
    $motor = app(MotorPagos::class);
    $estado = $motor->iniciar(productoMotorRacional('36', $gracia, $intereses), ['motor_calculo_version' => 2,
        'escenario' => ['fecha_disposicion' => '2026-09-01'], 'tabla' => [
            ['numero' => 1, 'fecha' => '2026-09-10', 'capital' => '1000.00'],
        ]]);
    $completo = $motor->avanzar($estado, '2026-09-14');
    expect($motor->avanzar($motor->avanzar($estado, '2026-09-12'), '2026-09-14'))->toBe($completo);
    $conceptos = collect($motor->resumen($completo)['conceptos'])->keyBy('tipo');
    expect($conceptos['ordinario']['importe'])->toBe($ordinario)->and($conceptos['moratorio']['importe'])->toBe($mora);
})->with([
    ['efectiva', 'ambos', '13.00', '2.00'], ['efectiva', 'sustituye', '12.00', '2.00'],
    ['retroactiva', 'ambos', '13.00', '8.00'], ['retroactiva', 'sustituye', '9.00', '8.00'],
]);

test('el motor corregido rechaza tablas de capital fijo que agotan el saldo antes del final', function () {
    $inicio = CarbonImmutable::parse('2026-01-01');
    $fechas = app(CalendarioCreditoSimple::class)->fechas($inicio, PeriodicidadCredito::Semanal, 12);
    expect(fn () => app(TablaAmortizacionCreditoSimple::class)->calcular('0.06', '0', $inicio, $fechas, MetodoAmortizacion::CapitalFijo, VersionMotorCredito::Act360Racional))
        ->toThrow(ValidationException::class, 'no permiten amortizaciones positivas');
});

test('el motor corregido rechaza la amortización negativa con meses desiguales', function () {
    $inicio = CarbonImmutable::parse('2026-02-28');
    $fechas = app(CalendarioCreditoSimple::class)->fechas($inicio, PeriodicidadCredito::Mensual, 60);
    expect(fn () => app(TablaAmortizacionCreditoSimple::class)->calcular('10000', '360', $inicio, $fechas, MetodoAmortizacion::CuotaNivelada, VersionMotorCredito::Act360Racional))
        ->toThrow(ValidationException::class, 'no permiten amortizaciones positivas');
});

test('cada cuota contractual puntual cierra y conserva el capital con el motor corregido', function (MetodoAmortizacion $metodo, PeriodicidadCredito $periodicidad) {
    $inicio = CarbonImmutable::parse('2028-01-31');
    $fechas = app(CalendarioCreditoSimple::class)->fechas($inicio, $periodicidad, 12);
    $tabla = app(TablaAmortizacionCreditoSimple::class)->calcular('1234.56', '24', $inicio, $fechas, $metodo, VersionMotorCredito::Act360Racional);
    $motor = app(MotorPagos::class);
    $estado = $motor->iniciar(productoMotorRacional('24'), ['motor_calculo_version' => 2,
        'escenario' => ['fecha_disposicion' => $inicio->toDateString()], 'tabla' => $tabla['tabla']]);
    foreach ($tabla['tabla'] as $fila) {
        expect(Decimal::compare($fila['capital'], '0'))->toBeGreaterThan(0)
            ->and(Decimal::compare($fila['saldo_final'], '0'))->toBeGreaterThanOrEqual(0);
        $estado = $motor->avanzar($estado, $fila['fecha']);
        expect($motor->resumen($estado)['exigible'])->toBe($fila['pago_total']);
        $estado = $motor->aplicar($estado, $fila['pago_total'])['estado'];
    }
    expect($motor->resumen($estado)['capital_insoluto'])->toBe('0.00')
        ->and($motor->resumen($estado)['exigible'])->toBe('0.00');
})->with(MetodoAmortizacion::cases())->with(PeriodicidadCredito::cases());

test('no mezcla versiones ni acepta un motor desconocido', function () {
    expect(fn () => VersionMotorCredito::desdeSnapshot(['motor_calculo_version' => 99]))
        ->toThrow(ValidationException::class, 'no está soportada');
    expect(fn () => app(MotorPagos::class)->iniciar(productoMotorRacional(), []))
        ->toThrow(ValidationException::class, 'versiones de cálculo diferentes');
});

test('no interpreta una marca inválida como evidencia histórica', function (mixed $version) {
    expect(fn () => VersionMotorCredito::desdeSnapshot(['motor_calculo_version' => $version]))
        ->toThrow(ValidationException::class, 'no está soportada');
})->with([null, false, '2', 99]);

test('no reconstruye un acumulador racional perdido desde un interés truncado', function () {
    $motor = app(MotorPagos::class);
    $estado = $motor->iniciar(productoMotorRacional('20'), ['motor_calculo_version' => 2,
        'escenario' => ['fecha_disposicion' => '2026-01-01'], 'tabla' => [
            ['numero' => 1, 'fecha' => '2026-01-10', 'capital' => '1.00'],
        ]]);
    $estado = $motor->avanzar($estado, '2026-01-05');
    unset($estado['cargos']['1:ordinario']['generado_numerador']);
    expect(fn () => $motor->avanzar($estado, '2026-01-06'))
        ->toThrow(ValidationException::class, 'no conserva su acumulador');
});
