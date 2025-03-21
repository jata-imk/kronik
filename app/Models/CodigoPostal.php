<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodigoPostal extends Model
{
    protected $table = 'codigos_postales';

    protected $fillable = [
        'codigo',
        'pais_id',
        'division_admin_id',
        'datos_adicionales',
    ];

    protected $casts = [
        'datos_adicionales' => 'json',
    ];
}
