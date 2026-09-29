<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SolicitudFormalizacion extends Model
{
    protected $table = 'solicitud_formalizaciones';

    protected $guarded = [];

    protected $hidden = ['snapshot'];

    protected $casts = ['snapshot' => 'encrypted:array'];

    protected static function booted(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['firma' => 'La formalización es inmutable; devuelve la solicitud para una nueva revisión.']);
        static::updating($reject);
        static::deleting($reject);
    }
}
