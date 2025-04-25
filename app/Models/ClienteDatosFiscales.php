<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteDatosFiscales extends Model
{
    protected $table = 'clientes_datos_fiscales';
    protected $fillable = [
        'cliente_id',
        'tipo_persona',
        'regimen_fiscal_id',
        'curp',
        'rfc',
        'razon_social',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function regimenFiscal()
    {
        return $this->belongsTo(RegimenFiscal::class);
    }
}
