<?php

namespace App\Models;

use App\Models\Concerns\ConservaRegistroCredito;
use Illuminate\Database\Eloquent\Model;

class CreditoMovimiento extends Model
{
    use ConservaRegistroCredito;

    protected $guarded = [];

    protected $casts = ['importe' => 'decimal:2', 'fecha_efectiva' => 'date:Y-m-d'];
}
