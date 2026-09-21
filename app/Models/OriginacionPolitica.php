<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class OriginacionPolitica extends Model
{
    protected $guarded = [];

    protected $casts = ['condiciones' => 'array'];

    protected static function booted(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['politica' => 'La política es inmutable. Registra una nueva versión.']);
        static::updating($reject);
        static::deleting($reject);
    }
}
