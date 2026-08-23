<?php

namespace App\Services;

use App\Models\EmpresaConfiguracion;
use Carbon\CarbonImmutable;

final class FechaEmpresa
{
    public function zonaHoraria(): string
    {
        return EmpresaConfiguracion::query()->value('zona_horaria') ?? config('app.timezone');
    }

    public function hoy(): CarbonImmutable
    {
        return CarbonImmutable::now($this->zonaHoraria())->startOfDay();
    }
}
