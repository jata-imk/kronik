<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DivisionAdministrativa extends Model
{
    protected $table = 'divisiones_administrativas';
    protected $fillable = [
        'pais_id',
        'nombre',
        'codigo',
        'nivel',
        'division_padre_id',
        'tipo',
    ];
}
