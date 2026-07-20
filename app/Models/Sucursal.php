<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'clave',
        'domicilio',
        'telefono',
        'email',
        'horario',
        'prefijo_folio',
        'consecutivo_solicitud',
        'consecutivo_contrato',
        'consecutivo_credito',
        'consecutivo_recibo',
        'activa',
    ];

    protected $casts = [
        'domicilio' => 'array',
        'horario' => 'array',
        'activa' => 'boolean',
    ];
}
