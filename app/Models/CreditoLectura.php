<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditoLectura extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['estado_motor', 'estado_hash', 'fuente_hash', 'condiciones_hash', 'original_hash', 'cronograma_hash', 'pago_hash'];

    protected $casts = [
        'estado_motor' => 'encrypted:array', 'corte' => 'date:Y-m-d', 'calculada_at' => 'immutable_datetime',
        'capital' => 'decimal:2', 'exigible' => 'decimal:2', 'vencido' => 'decimal:2',
    ];
}
