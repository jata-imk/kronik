<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $fillable = [
        'primer_nombre',
        'segundo_nombre',
        'apellido_paterno',
        'apellido_materno',

        'fecha_nacimiento',
        'pais_nacimiento_id',
        'telefono_codigo_pais',
        'telefono',
        'email',
        'sexo',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'datetime',
    ];

    public function paisNacimiento()
    {
        return $this->belongsTo(Pais::class, 'pais_nacimiento_id');
    }

    public function datosFiscales()
    {
        return $this->hasOne(ClienteDatosFiscales::class);
    }

    public function direcciones()
    {
        return $this->morphMany(Direccion::class, 'entidad', 'entidad_tipo', 'entidad_id');
    }

    public function sicQueries()
    {
        return $this->hasMany(SicQuery::class);
    }

    public function getMorphClass()
    {
        return 'clientes';
    }
}
