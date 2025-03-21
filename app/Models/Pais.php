<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    protected $table = 'paises';
    protected $fillable = [
        'nombre_es',
        'nombre_us',
        'nombre_nativo',
        'idiomas',
        'codigo_iso',
        'codigo_iso3',
        'emoji',
        'mapas',
        'formato_direccion',
    ];

    protected $casts = [
        'nombre_nativo' => 'json',
        'idiomas' => 'json',
        'mapas' => 'json',
        'formato_direccion' => 'json',
    ];
}
