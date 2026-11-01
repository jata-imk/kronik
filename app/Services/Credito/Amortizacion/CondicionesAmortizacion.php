<?php

namespace App\Services\Credito\Amortizacion;

use App\Enums\ConvencionInteres;
use App\Enums\MetodoAmortizacion;
use App\Enums\VersionMotorCredito;
use Carbon\CarbonImmutable;

final readonly class CondicionesAmortizacion
{
    /** @param list<CarbonImmutable> $fechas */
    public function __construct(
        public string $principal,
        public string $tasaAnualPorcentaje,
        public CarbonImmutable $inicio,
        public array $fechas,
        public MetodoAmortizacion $metodo,
        public VersionMotorCredito $motor,
        public ConvencionInteres $convencion,
    ) {}
}
