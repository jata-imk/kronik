<?php

namespace App\Services\Credito;

use App\Enums\PeriodicidadCredito;
use App\Support\Decimal;
use InvalidArgumentException;
use RuntimeException;

final class CatInformativoService
{
    /** @param array<int, string> $pagos */
    public function calcular(string $montoRecibido, array $pagos, PeriodicidadCredito $periodicidad, ?int $diasAlVencimiento = null): string
    {
        if (Decimal::compare($montoRecibido, '0') <= 0 || $pagos === []) {
            throw new InvalidArgumentException('El CAT requiere un monto recibido y pagos positivos.');
        }

        if ($diasAlVencimiento !== null && $diasAlVencimiento > 0
            && Decimal::compare((string) end($pagos), '0') > 0
            && count(array_filter($pagos, fn ($pago) => Decimal::compare($pago, '0') !== 0)) === 1) {
            return $this->calcularPagoUnico($montoRecibido, (string) end($pagos), $diasAlVencimiento);
        }

        $inferior = '0';
        $superior = '1';
        while (Decimal::compare($this->valorPresente($pagos, $superior), $montoRecibido) > 0) {
            $superior = Decimal::mul($superior, '2');
            if (Decimal::compare($superior, '1024') > 0) {
                throw new RuntimeException('No fue posible determinar el CAT para estos flujos.');
            }
        }
        for ($i = 0; $i < 160; $i++) {
            $medio = Decimal::div(Decimal::add($inferior, $superior), '2');
            if (Decimal::compare($this->valorPresente($pagos, $medio), $montoRecibido) > 0) {
                $inferior = $medio;
            } else {
                $superior = $medio;
            }
        }
        $periodica = Decimal::div(Decimal::add($inferior, $superior), '2');
        $anual = bcsub(bcpow(Decimal::add('1', $periodica), (string) $periodicidad->periodosAnuales(), Decimal::SCALE), '1', Decimal::SCALE);

        return Decimal::round(Decimal::mul($anual, '100'), 1);
    }

    private function calcularPagoUnico(string $monto, string $pago, int $dias): string
    {
        $divisor = 360;
        for ($resto = $dias % $divisor; $resto !== 0; $resto = $restoSiguiente) {
            $restoSiguiente = $divisor % $resto;
            $divisor = $resto;
        }
        $periodos = (string) intdiv(360, $divisor);
        $duracion = (string) intdiv($dias, $divisor);
        $escala = bcpow('10', (string) Decimal::SCALE, 0);
        $montoElevado = bcpow(bcmul($monto, $escala, 0), $periodos, 0);
        $pagoElevado = bcpow(bcmul($pago, $escala, 0), $periodos, 0);
        $pagoEscalado = bcmul($pagoElevado, bcpow('2000', $duracion, 0), 0);

        // La décima k empieza en (k - 0.5)/1000. Elevar ambos lados
        // elimina los exponentes fraccionarios y conserva los empates exactos.
        $alcanza = function (string $decimas) use ($montoElevado, $pagoEscalado, $duracion): bool {
            $numerador = bcadd('1999', bcmul('2', $decimas, 0), 0);

            return bccomp($pagoEscalado, bcmul($montoElevado, bcpow($numerador, $duracion, 0), 0), 0) >= 0;
        };
        $inferior = '0';
        $superior = '1';
        while ($alcanza($superior)) {
            $inferior = $superior;
            $superior = bcmul('2', $superior, 0);
        }
        while (bccomp(bcsub($superior, $inferior, 0), '1', 0) > 0) {
            $medio = bcdiv(bcadd($inferior, $superior, 0), '2', 0);
            if ($alcanza($medio)) {
                $inferior = $medio;
            } else {
                $superior = $medio;
            }
        }

        return bcdiv($inferior, '10', 1);
    }

    /** @param array<int, string> $pagos */
    private function valorPresente(array $pagos, string $tasaPeriodica): string
    {
        $base = Decimal::add('1', $tasaPeriodica);
        $total = '0';
        foreach (array_values($pagos) as $index => $pago) {
            $total = Decimal::add($total, Decimal::div($pago, bcpow($base, (string) ($index + 1), Decimal::SCALE)));
        }

        return $total;
    }
}
