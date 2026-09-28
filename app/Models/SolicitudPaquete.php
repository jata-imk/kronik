<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SolicitudPaquete extends Model
{
    protected $table = 'solicitud_paquetes';

    protected $guarded = [];

    protected $hidden = ['snapshot'];

    protected $casts = ['snapshot' => 'encrypted:array'];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function documento()
    {
        return $this->morphOne(DocumentoGenerado::class, 'documentable');
    }

    protected static function booted(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['paquete' => 'El paquete es inmutable. Devuelve y reenvía la solicitud para preparar otro con una nueva aprobación.']);
        static::updating($reject);
        static::deleting($reject);
    }
}
