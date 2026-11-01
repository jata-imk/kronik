<?php

namespace App\Contracts\Credito;

use App\Services\Credito\Amortizacion\BaseAmortizacion;

interface EstrategiaAmortizacion
{
    /** @param list<string> $descuentos */
    public function preparar(string $principal, array $descuentos): BaseAmortizacion;

    public function capital(string $base, string $interes): string;

    public function baseDesdeCuota(string $capital, string $interes): string;
}
