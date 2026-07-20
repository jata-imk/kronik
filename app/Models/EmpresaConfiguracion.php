<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaConfiguracion extends Model
{
    protected $table = 'empresa_configuraciones';

    protected $fillable = [
        'singleton_key',
        'razon_social',
        'nombre_comercial',
        'rfc',
        'regimen_fiscal',
        'domicilio_fiscal',
        'telefono',
        'email',
        'sitio_web',
        'moneda',
        'zona_horaria',
        'pais_base',
        'logotipo_path',
        'parametros_operativos',
        'integraciones',
        'estatus',
    ];

    protected $casts = [
        'domicilio_fiscal' => 'array',
        'parametros_operativos' => 'array',
        'integraciones' => 'encrypted:array',
    ];
}
