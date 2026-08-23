<?php

namespace App\Services\Credito;

use App\Enums\PeriodicidadCredito;
use Carbon\CarbonImmutable;

final class CalendarioCreditoSimple
{
    /** @return array<int, CarbonImmutable> */
    public function fechas(CarbonImmutable $inicio, PeriodicidadCredito $periodicidad, int $plazo): array
    {
        $fechas = [];

        for ($i = 1; $i <= $plazo; $i++) {
            $fechas[] = match ($periodicidad) {
                PeriodicidadCredito::Semanal => $inicio->addDays(7 * $i),
                PeriodicidadCredito::Quincenal => $inicio->addDays(15 * $i),
                PeriodicidadCredito::Mensual => $inicio->isLastOfMonth()
                    ? $inicio->addMonthsNoOverflow($i)->endOfMonth()->startOfDay()
                    : $inicio->addMonthsNoOverflow($i),
            };
        }

        return $fechas;
    }
}
