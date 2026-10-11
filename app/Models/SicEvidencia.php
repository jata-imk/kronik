<?php

namespace App\Models;

use App\Models\Concerns\ConservaEvidenciaSic;
use Illuminate\Database\Eloquent\Model;

class SicEvidencia extends Model
{
    use ConservaEvidenciaSic;

    protected $guarded = [];

    protected $hidden = ['contenido'];

    protected $casts = ['contenido' => 'encrypted:array'];
}
