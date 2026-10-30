<?php

namespace App\Services\Credito;

use App\Enums\MetodoAmortizacion;
use App\Enums\VersionMotorCredito;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class TablaAmortizacionCreditoSimple
{
    public function __construct(private readonly InteresAct360 $intereses) {}

    /**
     * @param  array<int, CarbonImmutable>  $fechas
     * @return array{tabla: array<int, array<string, int|string>>, cuota_exacta: ?string, cuota_redondeada: ?string}
     */
    public function calcular(string $principal, string $tasaAnualPorcentaje, CarbonImmutable $inicio, array $fechas, MetodoAmortizacion $metodo, VersionMotorCredito $motor = VersionMotorCredito::Historico): array
    {
        if ($motor !== VersionMotorCredito::Historico && ($fechas === [] || Decimal::compare(Decimal::round($principal), '0') <= 0)) {
            $this->error('Indica un capital de al menos un centavo y un calendario con vencimientos.');
        }
        if ($motor !== VersionMotorCredito::Historico && Decimal::compare($tasaAnualPorcentaje, '0') < 0) {
            $this->error('La tasa ordinaria anual no puede ser negativa.');
        }
        $factores = $this->factores($tasaAnualPorcentaje, $inicio, $fechas, $motor);
        $cuotaExacta = $metodo === MetodoAmortizacion::CuotaNivelada
            ? Decimal::div($principal, array_reduce($factores, fn (string $total, array $factor) => Decimal::add($total, $factor['descuento']), '0'))
            : null;
        $cuotaRedondeada = $cuotaExacta === null ? null : Decimal::round($cuotaExacta);
        $capitalFijo = Decimal::round(Decimal::div($principal, (string) count($fechas)));
        $saldo = Decimal::round($principal);
        $tabla = [];

        foreach ($factores as $index => $factor) {
            $saldoInicial = $saldo;
            $interes = Decimal::round($motor === VersionMotorCredito::Historico
                ? Decimal::mul($saldoInicial, $factor['tasa'])
                : $this->intereses->calcular($saldoInicial, $tasaAnualPorcentaje, $factor['dias']));
            $ultimo = $index === array_key_last($factores);
            $capital = $ultimo
                ? $saldoInicial
                : ($metodo === MetodoAmortizacion::CuotaNivelada
                    ? Decimal::sub((string) $cuotaRedondeada, $interes, 2)
                    : $capitalFijo);
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

        return ['tabla' => $tabla, 'cuota_exacta' => $cuotaExacta, 'cuota_redondeada' => $cuotaRedondeada];
    }

    /** @param array<int, CarbonImmutable> $fechas */
    private function factores(string $tasaAnualPorcentaje, CarbonImmutable $inicio, array $fechas, VersionMotorCredito $motor): array
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
                : $this->intereses->calcular('1', $tasaAnualPorcentaje, $dias);
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

    private function error(string $mensaje): never
    {
        throw ValidationException::withMessages(['metodo' => $mensaje]);
    }
}
