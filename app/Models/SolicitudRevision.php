<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SolicitudRevision extends Model
{
    protected $table = 'solicitud_revisiones';

    protected $guarded = [];

    protected $casts = ['snapshot' => 'array'];

    protected static function booted(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['solicitud' => 'La revisión es inmutable. Crea una nueva revisión.']);
        static::updating($reject);
        static::deleting($reject);
    }
}
