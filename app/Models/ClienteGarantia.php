<?php

namespace App\Models;

use App\Enums\ClienteGarantiaTipo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteGarantia extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'propietario_cliente_id',
        'tipo',
        'descripcion',
        'valor_estimado',
        'moneda',
        'notas',
    ];

    protected $casts = [
        'tipo' => ClienteGarantiaTipo::class,
        'valor_estimado' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function propietario()
    {
        return $this->belongsTo(Cliente::class, 'propietario_cliente_id');
    }
}
