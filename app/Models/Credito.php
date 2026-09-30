<?php

namespace App\Models;

use App\Models\Concerns\ConservaRegistroCredito;
use Illuminate\Database\Eloquent\Model;

class Credito extends Model
{
    use ConservaRegistroCredito;

    protected $guarded = [];

    protected $hidden = ['condiciones'];

    protected $casts = ['condiciones' => 'encrypted:array', 'capital_inicial' => 'decimal:2', 'fecha_desembolso' => 'date:Y-m-d'];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function desembolso()
    {
        return $this->hasOne(CreditoDesembolso::class);
    }

    public function cronogramas()
    {
        return $this->hasMany(CreditoCronograma::class);
    }

    public function movimientos()
    {
        return $this->hasMany(CreditoMovimiento::class);
    }

    public function pagos()
    {
        return $this->hasMany(CreditoPago::class);
    }
}
