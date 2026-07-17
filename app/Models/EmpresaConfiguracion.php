<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaConfiguracion extends Model
{
    use HasFactory;

    protected $table = 'empresa_configuraciones';

    protected $fillable = [
        'team_id',
        'razon_social',
        'nombre_comercial',
        'rfc',
        'regimen_fiscal_id',
        'email',
        'telefono',
        'sitio_web',
        'logo_path',
        'domicilio_fiscal',
        'moneda',
        'zona_horaria',
        'horario_operacion',
        'folio_credito_prefijo',
        'folio_credito_siguiente',
        'dias_inhabiles',
        'reglas_cobranza',
        'formatos_contrato',
        'cuentas_bancarias',
        'contactos',
        'integraciones',
        'activa',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'domicilio_fiscal' => 'array',
            'horario_operacion' => 'array',
            'dias_inhabiles' => 'array',
            'reglas_cobranza' => 'array',
            'formatos_contrato' => 'array',
            'cuentas_bancarias' => 'array',
            'contactos' => 'array',
            'integraciones' => 'array',
            'activa' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function regimenFiscal(): BelongsTo
    {
        return $this->belongsTo(RegimenFiscal::class);
    }
}
