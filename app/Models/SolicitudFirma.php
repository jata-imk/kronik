<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SolicitudFirma extends Model
{
    protected $guarded = [];

    protected $hidden = ['disk', 'path', 'idempotency_key', 'motivo'];

    protected $casts = ['fecha_firma' => 'date:Y-m-d', 'revisada_en' => 'datetime', 'motivo' => 'encrypted'];

    public function paquete()
    {
        return $this->belongsTo(SolicitudPaquete::class, 'solicitud_paquete_id');
    }

    public function receptor()
    {
        return $this->belongsTo(User::class, 'recibida_por');
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }

    protected static function booted(): void
    {
        static::updating(function (self $firma): void {
            if ($firma->getRawOriginal('estado') !== 'recibida'
                || array_diff(array_keys($firma->getDirty()), ['estado', 'revisada_por', 'revisada_en', 'motivo', 'updated_at'])
                || ! in_array($firma->estado, ['aceptada', 'rechazada'], true)) {
                throw ValidationException::withMessages(['firma' => 'La evidencia de firma se conserva; recibe otra copia después de rechazar la anterior.']);
            }
        });
        static::deleting(fn () => throw ValidationException::withMessages(['firma' => 'No puedes borrar evidencia de firma.']));
    }
}
