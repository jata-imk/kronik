<?php

namespace App\Services\Credito\Amortizacion;

use App\Contracts\Credito\EstrategiaAmortizacion;
use App\Enums\MetodoAmortizacion;

final class EstrategiasAmortizacion
{
    public function resolver(MetodoAmortizacion $metodo): EstrategiaAmortizacion
    {
        return match ($metodo) {
            MetodoAmortizacion::CuotaNivelada => new CuotaNivelada,
            MetodoAmortizacion::CapitalFijo => new CapitalFijo,
            MetodoAmortizacion::Bullet => new Bullet,
        };
    }
}
