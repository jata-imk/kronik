<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaConfiguracion extends Model
{
    protected $table = 'empresa_configuraciones';

    protected $fillable = [
        'singleton_key',
        'razon_social',
        'nombre_comercial',
        'tipo_persona',
        'rfc',
        'regimen_fiscal_id',
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

    public function regimenFiscal(): BelongsTo
    {
        return $this->belongsTo(RegimenFiscal::class);
    }
}
