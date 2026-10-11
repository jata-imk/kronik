<?php

namespace App\Models\Concerns;

use Illuminate\Validation\ValidationException;

trait ConservaEvidenciaSic
{
    protected static function bootConservaEvidenciaSic(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['sic' => 'La evidencia SIC es inmutable. No puedes editar ni borrar su historia.']);
        static::updating($reject);
        static::deleting($reject);
    }
}
