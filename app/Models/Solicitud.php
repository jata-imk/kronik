<?php

namespace App\Models;

use App\Enums\SolicitudEstado;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $guarded = [];

    protected $hidden = ['clave_creacion', 'creacion_hash'];

    protected $casts = [
        'estado' => SolicitudEstado::class,
        'monto' => 'decimal:2',
        'fecha_estimada' => 'date:Y-m-d',
        'enviada_en' => 'datetime',
        'lock_version' => 'integer',
    ];

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

    public function productoVersion()
    {
        return $this->belongsTo(ProductoVersion::class);
    }

    public function revisiones()
    {
        return $this->hasMany(SolicitudRevision::class);
    }

    public function eventos()
    {
        return $this->hasMany(SolicitudEvento::class);
    }

    public function resoluciones()
    {
        return $this->hasMany(SolicitudResolucion::class);
    }

    public function dictamenes()
    {
        return $this->hasMany(SolicitudDictamen::class);
    }
}
