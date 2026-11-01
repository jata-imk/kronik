<?php

namespace App\Services\Credito\Amortizacion;

use App\Enums\ConvencionInteres;
use App\Enums\VersionMotorCredito;
use App\Services\Credito\InteresPorPeriodo;
use App\Support\Decimal;
use Carbon\CarbonImmutable;

final class CalculadorAmortizacion
{
    public function __construct(private readonly InteresPorPeriodo $intereses, private readonly EstrategiasAmortizacion $estrategias) {}

    public function calcular(CondicionesAmortizacion $condiciones): ResultadoAmortizacion
    {
        ['principal' => $principal, 'tasaAnualPorcentaje' => $tasaAnualPorcentaje, 'inicio' => $inicio,
            'fechas' => $fechas, 'metodo' => $metodo, 'motor' => $motor, 'convencion' => $convencion] = get_object_vars($condiciones);
        if ($motor !== VersionMotorCredito::Historico && ($fechas === [] || Decimal::compare(Decimal::round($principal), '0') <= 0)) {
            $this->error('Indica un capital de al menos un centavo y un calendario con vencimientos.');
        }
        if ($motor !== VersionMotorCredito::Historico && Decimal::compare($tasaAnualPorcentaje, '0') < 0) {
            $this->error('La tasa ordinaria anual no puede ser negativa.');
        }
        $factores = $this->factores($tasaAnualPorcentaje, $inicio, $fechas, $motor, $convencion);
        $estrategia = $this->estrategias->resolver($metodo);
        $base = $estrategia->preparar($principal, array_column($factores, 'descuento'));
        $saldo = Decimal::round($principal);
        $tabla = [];

        foreach ($factores as $index => $factor) {
            $saldoInicial = $saldo;
            $interes = Decimal::round($motor === VersionMotorCredito::Historico
                ? Decimal::mul($saldoInicial, $factor['tasa'])
                : $this->intereses->calcular($saldoInicial, $tasaAnualPorcentaje, $factor['inicio'], $factor['fecha'], $convencion));
            $ultimo = $index === array_key_last($factores);
            $capital = $ultimo
                ? $saldoInicial
                : $estrategia->capital($base->importe, $interes);
            $capital = Decimal::round($capital);
            if ($motor !== VersionMotorCredito::Historico && (Decimal::compare($capital, '0') <= 0 || Decimal::compare($capital, $saldoInicial) > 0)) {
                $this->error('El monto, la tasa y el calendario no permiten amortizaciones positivas dentro del capital pendiente. Revisa el plazo, el monto o el método seleccionado.');
            }
            $pago = Decimal::round(Decimal::add($capital, $interes));
            $saldo = Decimal::round(Decimal::sub($saldoInicial, $capital));

            $tabla[] = [
                'numero' => $index + 1,
                'tipo' => 'pago',
                'fecha' => $factor['fecha']->toDateString(),
                'dias' => $factor['dias'],
                'saldo_inicial' => $saldoInicial,
                'disposicion' => '0.00',
                'capital' => $capital,
                'interes' => $interes,
                'comisiones' => '0.00',
                'pago_total' => $pago,
                'pago_cat' => $pago,
                'saldo_final' => $saldo,
                'saldo' => $saldo,
            ];
        }

        return new ResultadoAmortizacion($tabla, $base);
    }

    /** @param array<int, CarbonImmutable> $fechas */
    private function factores(string $tasaAnualPorcentaje, CarbonImmutable $inicio, array $fechas, VersionMotorCredito $motor, ConvencionInteres $convencion): array
    {
        $anterior = $inicio;
        $acumulado = '1';
        $factores = [];

        foreach ($fechas as $fecha) {
            $dias = (int) round($anterior->diffInDays($fecha));
            if ($motor !== VersionMotorCredito::Historico && $dias <= 0) {
                $this->error('Los vencimientos deben ser posteriores al inicio y avanzar en orden, sin fechas repetidas.');
            }
            $tasa = $motor === VersionMotorCredito::Historico
                ? Decimal::mul(Decimal::div($tasaAnualPorcentaje, '100'), Decimal::div((string) $dias, '360'))
                : $this->intereses->calcular('1', $tasaAnualPorcentaje, $anterior, $fecha, $convencion);
            $acumulado = Decimal::mul($acumulado, Decimal::add('1', $tasa));
            $factores[] = [
                'inicio' => $anterior,
                'fecha' => $fecha,
                'dias' => $dias,
                'tasa' => $tasa,
                'descuento' => Decimal::div('1', $acumulado),
            ];
            $anterior = $fecha;
        }

        return $factores;
    }

    private function error(string $mensaje): never
    {
        throw new AmortizacionInvalida($mensaje);
    }
}
