<?php

namespace App\Services\Credito;

use App\Support\Decimal;
use InvalidArgumentException;

/** Conserva el numerador exacto; divide después de acumular las exposiciones. */
final class InteresAct360
{
    public function numerador(string $capital, string $tasaAnualPorcentaje, int $dias): string
    {
        if ($dias < 0) {
            throw new InvalidArgumentException('Los días de devengo no pueden ser negativos.');
        }

        return Decimal::mul(Decimal::mul($capital, $tasaAnualPorcentaje), (string) $dias);
    }

    public function desdeNumerador(string $numerador): string
    {
        return Decimal::div($numerador, '36000');
    }

    public function calcular(string $capital, string $tasaAnualPorcentaje, int $dias): string
    {
        return $this->desdeNumerador($this->numerador($capital, $tasaAnualPorcentaje, $dias));
    }
}
