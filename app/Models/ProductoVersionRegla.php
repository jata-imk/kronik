<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoVersionRegla extends Model
{
    protected $table = 'producto_version_reglas';

    protected $guarded = [];

    protected $casts = ['metodos_amortizacion' => 'array', 'permite_prepago_parcial' => 'boolean', 'permite_liquidacion_anticipada' => 'boolean', 'monto_minimo_prepago' => 'decimal:4'];

    public function version()
    {
        return $this->belongsTo(ProductoVersion::class, 'producto_version_id');
    }
}
