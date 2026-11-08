<?php

namespace App\Services\Credito\Amortizacion;

use App\Contracts\Credito\EstrategiaAmortizacion;
use App\Support\Decimal;

final class CapitalFijo implements EstrategiaAmortizacion
{
    public function permiteCapitalIntermedioCero(): bool
    {
        return false;
    }

    public function preparar(string $principal, array $descuentos): BaseAmortizacion
    {
        return new BaseAmortizacion(Decimal::round(Decimal::div($principal, (string) count($descuentos))));
    }

    public function capital(string $base, string $interes): string
    {
        return $base;
    }

    public function baseDesdeCuota(string $capital, string $interes): string
    {
        return $capital;
    }
}
