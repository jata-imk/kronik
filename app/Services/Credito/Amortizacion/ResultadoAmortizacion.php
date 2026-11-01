<?php

namespace App\Services\Credito\Amortizacion;

final readonly class ResultadoAmortizacion
{
    /** @param list<array<string, int|string>> $tabla */
    public function __construct(public array $tabla, public BaseAmortizacion $base) {}

    public function toArray(): array
    {
        return ['tabla' => $this->tabla, 'cuota_exacta' => $this->base->cuotaExacta, 'cuota_redondeada' => $this->base->cuotaRedondeada];
    }
}
