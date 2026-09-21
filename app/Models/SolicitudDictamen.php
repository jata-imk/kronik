<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SolicitudDictamen extends Model
{
    protected $table = 'solicitud_dictamenes';

    protected $guarded = [];

    protected $hidden = ['contenido'];

    protected $casts = ['contenido' => 'encrypted:array'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function booted(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['dictamen' => 'El dictamen es inmutable. Registra uno nuevo para conservar la trazabilidad.']);
        static::updating($reject);
        static::deleting($reject);
    }
}
