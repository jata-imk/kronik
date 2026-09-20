<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SolicitudResolucion extends Model
{
    protected $table = 'solicitud_resoluciones';

    protected $guarded = [];

    protected $hidden = ['motivo'];

    protected $casts = ['motivo' => 'encrypted'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function booted(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['solicitud' => 'La resolución es inmutable. Registra una nueva operación.']);
        static::updating($reject);
        static::deleting($reject);
    }
}
