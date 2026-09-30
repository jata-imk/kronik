<?php

namespace App\Models;

use App\Models\Concerns\ConservaRegistroCredito;
use Illuminate\Database\Eloquent\Model;

class CreditoDesembolso extends Model
{
    use ConservaRegistroCredito;

    protected $guarded = [];

    protected $hidden = ['referencia', 'referencia_hash', 'idempotency_key', 'payload_hash'];

    protected $casts = ['referencia' => 'encrypted', 'importe' => 'decimal:2', 'fecha_efectiva' => 'date:Y-m-d'];
}
