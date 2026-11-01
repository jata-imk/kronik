<?php

namespace App\Contracts\Credito;

use Carbon\CarbonImmutable;

/** Convención con denominador anual fijo; no define amortización ni exigibilidad. */
interface ConvencionDias
{
    public function dias(CarbonImmutable $inicio, CarbonImmutable $fin): int;

    public function baseAnual(): int;
}
