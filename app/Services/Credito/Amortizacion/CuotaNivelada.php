<?php

namespace App\Services\Credito\Amortizacion;

use App\Contracts\Credito\EstrategiaAmortizacion;
use App\Support\Decimal;

final class CuotaNivelada implements EstrategiaAmortizacion
{
    public function permiteCapitalIntermedioCero(): bool
    {
        return false;
    }

    public function preparar(string $principal, array $descuentos): BaseAmortizacion
    {
        $exacta = Decimal::div($principal, array_reduce($descuentos, fn (string $total, string $factor) => Decimal::add($total, $factor), '0'));
        $redondeada = Decimal::round($exacta);

        return new BaseAmortizacion($redondeada, $exacta, $redondeada);
    }

    public function capital(string $base, string $interes): string
    {
        return Decimal::sub($base, $interes, 2);
    }

    public function baseDesdeCuota(string $capital, string $interes): string
    {
        return Decimal::add($capital, $interes, 2);
    }
}
