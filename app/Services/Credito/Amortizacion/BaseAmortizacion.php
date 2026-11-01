<?php

namespace App\Services\Credito\Amortizacion;

final readonly class BaseAmortizacion
{
    public function __construct(public string $importe, public ?string $cuotaExacta = null, public ?string $cuotaRedondeada = null) {}
}
