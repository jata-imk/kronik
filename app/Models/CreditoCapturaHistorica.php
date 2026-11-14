<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** Control operativo separado del libro financiero inmutable. */
class CreditoCapturaHistorica extends Model
{
    protected $table = 'credito_capturas_historicas';

    protected $guarded = [];

    protected $casts = ['completada_en' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (self $captura): void {
            if ($captura->getRawOriginal('completada_en') !== null
                || array_diff(array_keys($captura->getDirty()), ['completada_por', 'completada_team_id', 'completada_en', 'updated_at']) !== []
                || ! $captura->completada_en || ! $captura->completada_por) {
                throw ValidationException::withMessages(['captura_historica' => 'Solo se permite completar una vez la captura; no se modifica su origen.']);
            }
        });
        static::deleting(fn () => throw ValidationException::withMessages(['captura_historica' => 'No se elimina la evidencia de captura histórica.']));
    }
}
