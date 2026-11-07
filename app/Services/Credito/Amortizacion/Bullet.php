<?php

namespace App\Services\Credito\Amortizacion;

use App\Contracts\Credito\EstrategiaAmortizacion;

/** Distribuye principal; no decide cuándo vence el interés. */
final class Bullet implements EstrategiaAmortizacion
{
    public function permiteCapitalIntermedioCero(): bool
    {
        return true;
    }

    public function preparar(string $principal, array $descuentos): BaseAmortizacion
    {
        return new BaseAmortizacion('0.00');
    }

    public function capital(string $base, string $interes): string
    {
        return '0.00';
    }

    public function baseDesdeCuota(string $capital, string $interes): string
    {
        return '0.00';
    }
}
