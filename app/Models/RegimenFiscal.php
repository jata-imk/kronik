<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegimenFiscal extends Model
{
    protected $table = 'regimenes_fiscales';
    protected $fillable = [
        'clave',
        'descripcion',
        'fisica',
        'moral',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
    ];

    protected $casts = ['fisica' => 'boolean', 'moral' => 'boolean'];
}
