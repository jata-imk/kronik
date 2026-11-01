<?php

namespace App\Services\Credito\Convenciones;

use App\Contracts\Credito\ConvencionDias;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class DiasReales360 implements ConvencionDias
{
    public function dias(CarbonImmutable $inicio, CarbonImmutable $fin): int
    {
        // Fechas civiles, independientes de la hora o del horario de verano.
        $desde = CarbonImmutable::parse($inicio->toDateString(), 'UTC');
        $hasta = CarbonImmutable::parse($fin->toDateString(), 'UTC');
        $dias = (int) $desde->diffInDays($hasta);
        if ($dias < 0) {
            throw new InvalidArgumentException('El fin del periodo de devengo no puede ser anterior al inicio.');
        }

        return $dias;
    }

    public function baseAnual(): int
    {
        return 360;
    }
}
