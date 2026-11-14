<?php

namespace App\Services\Credito;

final readonly class EstadoCredito
{
    public function __construct(
        public ?array $estado,
        public array $cronograma,
        public ?int $ultimoPagoId,
        public string $fuenteHash,
        public array $verificacion,
    ) {}
}
