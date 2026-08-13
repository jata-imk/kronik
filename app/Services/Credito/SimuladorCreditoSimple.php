<?php

namespace App\Services\Credito;

use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Models\ProductoVersion;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class SimuladorCreditoSimple
{
    public function __construct(private readonly CatInformativoService $catService) {}

    /** @return array<string, mixed> */
    public function simular(ProductoVersion $version, string $monto, PeriodicidadCredito $periodicidad, int $plazo, MetodoAmortizacion $metodo, CarbonImmutable $fecha): array
    {
        $version->loadMissing(['periodicidades', 'reglas', 'comisiones.concepto']);
        $this->validar($version, $monto, $periodicidad, $plazo, $metodo);
        $tabla = $this->tabla($version, $monto, $fecha, $this->fechas($fecha, $periodicidad, $plazo), $metodo);
        $inicial = '0';

        foreach ($version->comisiones->where('obligatoria', true) as $comision) {
            $importe = $comision->calcular($monto);
            if (in_array($comision->momento_cobro, ['firma', 'desembolso_descuento'], true)) {
                $inicial = Decimal::add($inicial, $importe);
            }
            if ($comision->momento_cobro === 'cada_pago') {
                foreach ($tabla as &$fila) {
                    $fila['comisiones'] = Decimal::round(Decimal::add($fila['comisiones'], $importe));
                    $fila['pago_total'] = Decimal::round(Decimal::add($fila['pago_total'], $importe));
                }
                unset($fila);
            }
        }

        $recibido = Decimal::sub($monto, $inicial);
        $cat = $version->cat_aplica
            ? $this->catService->calcular($recibido, array_column($tabla, 'pago_total'), $periodicidad)
            : null;

        return [
            'monto' => Decimal::round($monto),
            'monto_recibido_cat' => Decimal::round($recibido),
            'comisiones_iniciales' => Decimal::round($inicial),
            'periodicidad' => $periodicidad->value,
            'plazo' => $plazo,
            'metodo' => $metodo->value,
            'cat' => $cat,
            'cat_leyenda' => $cat === null ? $version->cat_no_aplica_motivo : 'CAT informativo. Sin IVA. Para fines informativos y de comparación exclusivamente.',
            'total_intereses' => Decimal::round(array_reduce($tabla, fn (string $total, array $fila) => Decimal::add($total, $fila['interes']), '0')),
            'total_pagar' => Decimal::round(array_reduce($tabla, fn (string $total, array $fila) => Decimal::add($total, $fila['pago_total']), '0')),
            'tabla' => $tabla,
        ];
    }

    /** @return array<int, CarbonImmutable> */
    private function fechas(CarbonImmutable $inicio, PeriodicidadCredito $periodicidad, int $plazo): array
    {
        $fechas = [];
        for ($i = 1; $i <= $plazo; $i++) {
            $fechas[] = match ($periodicidad) {
                PeriodicidadCredito::Semanal => $inicio->addDays(7 * $i),
                PeriodicidadCredito::Quincenal => $inicio->addDays(15 * $i),
                PeriodicidadCredito::Mensual => $inicio->isLastOfMonth() ? $inicio->addMonthsNoOverflow($i)->endOfMonth()->startOfDay() : $inicio->addMonthsNoOverflow($i),
            };
        }

        return $fechas;
    }

    /** @return array<int, array<string, string|int>> */
    private function tabla(ProductoVersion $version, string $monto, CarbonImmutable $inicio, array $fechas, MetodoAmortizacion $metodo): array
    {
        $anterior = $inicio;
        $factores = [];
        $acumulado = '1';
        foreach ($fechas as $fecha) {
            $dias = (int) round($anterior->diffInDays($fecha));
            $tasa = Decimal::mul(Decimal::div((string) $version->tasa_ordinaria_anual, '100'), Decimal::div((string) $dias, '360'));
            $acumulado = Decimal::mul($acumulado, Decimal::add('1', $tasa));
            $factores[] = ['fecha' => $fecha, 'dias' => $dias, 'tasa' => $tasa, 'descuento' => Decimal::div('1', $acumulado)];
            $anterior = $fecha;
        }
        $cuota = $metodo === MetodoAmortizacion::CuotaNivelada
            ? Decimal::div($monto, array_reduce($factores, fn (string $total, array $factor) => Decimal::add($total, $factor['descuento']), '0'))
            : null;
        $capitalFijo = Decimal::div($monto, (string) count($fechas));
        $saldo = $monto;
        $tabla = [];
        foreach ($factores as $index => $factor) {
            $interes = Decimal::mul($saldo, $factor['tasa']);
            $capital = $metodo === MetodoAmortizacion::CuotaNivelada ? Decimal::sub((string) $cuota, $interes) : $capitalFijo;
            if ($index === array_key_last($factores)) {
                $capital = $saldo;
            }
            $pago = Decimal::add($capital, $interes);
            $saldo = Decimal::sub($saldo, $capital);
            $tabla[] = [
                'numero' => $index + 1,
                'fecha' => $factor['fecha']->toDateString(),
                'dias' => $factor['dias'],
                'capital' => Decimal::round($capital),
                'interes' => Decimal::round($interes),
                'comisiones' => '0.00',
                'pago_total' => Decimal::round($pago),
                'saldo' => Decimal::round($saldo),
            ];
        }

        return $tabla;
    }

    private function validar(ProductoVersion $version, string $monto, PeriodicidadCredito $periodicidad, int $plazo, MetodoAmortizacion $metodo): void
    {
        $config = $version->periodicidades->firstWhere('periodicidad', $periodicidad->value);
        $errores = [];
        if (Decimal::compare($monto, (string) $version->monto_minimo) < 0 || Decimal::compare($monto, (string) $version->monto_maximo) > 0) {
            $errores['monto'] = 'El monto debe estar dentro del rango configurado para esta versión.';
        }
        if (! $config || $plazo < $config->plazo_minimo || $plazo > $config->plazo_maximo) {
            $errores['plazo'] = 'El plazo no está permitido para la periodicidad seleccionada.';
        }
        if (! in_array($metodo->value, $version->reglas?->metodos_amortizacion ?? [], true)) {
            $errores['metodo'] = 'El método de amortización no está habilitado para esta versión.';
        }
        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }
}
