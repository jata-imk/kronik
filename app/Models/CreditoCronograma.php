<?php

namespace App\Models;

use App\Models\Concerns\ConservaRegistroCredito;
use Illuminate\Database\Eloquent\Model;

class CreditoCronograma extends Model
{
    use ConservaRegistroCredito;

    protected $guarded = [];

    protected $hidden = ['snapshot'];

    protected $casts = ['snapshot' => 'encrypted:array'];
}
