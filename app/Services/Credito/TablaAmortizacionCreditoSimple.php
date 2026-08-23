<?php

namespace App\Services\Credito;

use App\Enums\MetodoAmortizacion;
use App\Support\Decimal;
use Carbon\CarbonImmutable;

final class TablaAmortizacionCreditoSimple
{
    /**
     * @param  array<int, CarbonImmutable>  $fechas
     * @return array{tabla: array<int, array<string, int|string>>, cuota_exacta: ?string, cuota_redondeada: ?string}
     */
    public function calcular(string $principal, string $tasaAnualPorcentaje, CarbonImmutable $inicio, array $fechas, MetodoAmortizacion $metodo): array
    {
        $factores = $this->factores($tasaAnualPorcentaje, $inicio, $fechas);
        $cuotaExacta = $metodo === MetodoAmortizacion::CuotaNivelada
            ? Decimal::div($principal, array_reduce($factores, fn (string $total, array $factor) => Decimal::add($total, $factor['descuento']), '0'))
            : null;
        $cuotaRedondeada = $cuotaExacta === null ? null : Decimal::round($cuotaExacta);
        $capitalFijo = Decimal::round(Decimal::div($principal, (string) count($fechas)));
        $saldo = Decimal::round($principal);
        $tabla = [];

        foreach ($factores as $index => $factor) {
            $saldoInicial = $saldo;
            $interes = Decimal::round(Decimal::mul($saldoInicial, $factor['tasa']));
            $ultimo = $index === array_key_last($factores);
            $capital = $ultimo
                ? $saldoInicial
                : ($metodo === MetodoAmortizacion::CuotaNivelada
                    ? Decimal::sub((string) $cuotaRedondeada, $interes, 2)
                    : $capitalFijo);
            $capital = Decimal::round($capital);
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

        return ['tabla' => $tabla, 'cuota_exacta' => $cuotaExacta, 'cuota_redondeada' => $cuotaRedondeada];
    }

    /** @param array<int, CarbonImmutable> $fechas */
    private function factores(string $tasaAnualPorcentaje, CarbonImmutable $inicio, array $fechas): array
    {
        $anterior = $inicio;
        $acumulado = '1';
        $factores = [];

        foreach ($fechas as $fecha) {
            $dias = (int) round($anterior->diffInDays($fecha));
            $tasa = Decimal::mul(Decimal::div($tasaAnualPorcentaje, '100'), Decimal::div((string) $dias, '360'));
            $acumulado = Decimal::mul($acumulado, Decimal::add('1', $tasa));
            $factores[] = [
                'fecha' => $fecha,
                'dias' => $dias,
                'tasa' => $tasa,
                'descuento' => Decimal::div('1', $acumulado),
            ];
            $anterior = $fecha;
        }

        return $factores;
    }
}
