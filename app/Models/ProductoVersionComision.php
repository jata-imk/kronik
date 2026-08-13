<?php

namespace App\Models;

use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;

class ProductoVersionComision extends Model
{
    protected $table = 'producto_version_comisiones';

    protected $guarded = [];

    protected $casts = ['importe' => 'decimal:8', 'obligatoria' => 'boolean', 'incluye_cat' => 'boolean'];

    public function concepto()
    {
        return $this->belongsTo(ConceptoComision::class, 'concepto_comision_id');
    }

    public function version()
    {
        return $this->belongsTo(ProductoVersion::class, 'producto_version_id');
    }

    public function calcular(string $monto): string
    {
        return $this->tipo_importe === 'porcentaje'
            ? Decimal::div(Decimal::mul($monto, (string) $this->importe), '100')
            : (string) $this->importe;
    }
}
