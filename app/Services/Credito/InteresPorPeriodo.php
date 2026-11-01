<?php

namespace App\Services\Credito;

use App\Enums\ConvencionInteres;
use App\Support\Decimal;
use Carbon\CarbonImmutable;

/** Precisión racional para convenciones de base fija; el capital lo aporta el llamador. */
final class InteresPorPeriodo
{
    public function __construct(private readonly ConvencionesDias $convenciones) {}

    public function numerador(string $capital, string $tasaAnualPorcentaje, CarbonImmutable $inicio, CarbonImmutable $fin, ConvencionInteres $convencion): string
    {
        $dias = $this->convenciones->resolver($convencion)->dias($inicio, $fin);

        return Decimal::mul(Decimal::mul($capital, $tasaAnualPorcentaje), (string) $dias);
    }

    public function desdeNumerador(string $numerador, ConvencionInteres $convencion): string
    {
        $divisor = Decimal::mul((string) $this->convenciones->resolver($convencion)->baseAnual(), '100');

        return Decimal::div($numerador, $divisor);
    }

    public function calcular(string $capital, string $tasaAnualPorcentaje, CarbonImmutable $inicio, CarbonImmutable $fin, ConvencionInteres $convencion): string
    {
        return $this->desdeNumerador($this->numerador($capital, $tasaAnualPorcentaje, $inicio, $fin, $convencion), $convencion);
    }
}
