<?php

namespace App\Models;

use App\Models\Concerns\ConservaRegistroCredito;
use Illuminate\Database\Eloquent\Model;

class CreditoPago extends Model
{
    use ConservaRegistroCredito;

    protected $guarded = [];

    protected $hidden = ['snapshot', 'referencia', 'motivo', 'referencia_hash', 'payload_hash', 'idempotency_key'];

    protected $casts = ['snapshot' => 'encrypted:array', 'referencia' => 'encrypted', 'motivo' => 'encrypted', 'importe' => 'decimal:2', 'fecha_efectiva' => 'date:Y-m-d'];

    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }

    public function reverso()
    {
        return $this->hasOne(self::class, 'reversa_de');
    }
}
