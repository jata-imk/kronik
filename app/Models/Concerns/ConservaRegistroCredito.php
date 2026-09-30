<?php

namespace App\Models\Concerns;

use Illuminate\Validation\ValidationException;

trait ConservaRegistroCredito
{
    protected static function bootConservaRegistroCredito(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['credito' => 'Este registro es inmutable. No puedes editar ni borrar la historia del crédito.']);
        static::updating($reject);
        static::deleting($reject);
    }
}
