<?php

namespace App\Services\Credito;

use App\Enums\PeriodicidadCredito;
use App\Support\Decimal;
use InvalidArgumentException;
use RuntimeException;

final class CatInformativoService
{
    /** @param array<int, string> $pagos */
    public function calcular(string $montoRecibido, array $pagos, PeriodicidadCredito $periodicidad): string
    {
        if (Decimal::compare($montoRecibido, '0') <= 0 || $pagos === []) {
            throw new InvalidArgumentException('El CAT requiere un monto recibido y pagos positivos.');
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
