<?php

namespace App\Services\Credito;

use App\Contracts\Credito\ConvencionDias;
use App\Enums\ConvencionInteres;
use App\Services\Credito\Convenciones\DiasReales360;

final class ConvencionesDias
{
    public function resolver(ConvencionInteres $convencion): ConvencionDias
    {
        return match ($convencion) {
            ConvencionInteres::DiasReales360 => new DiasReales360,
        };
    }
}
