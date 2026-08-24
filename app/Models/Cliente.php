<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    use HasFactory;

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
        'ocupacion',
        'actividad_economica',
        'ingresos_mensuales',
        'egresos_mensuales',
        'origen_recursos',
        'sucursal_id',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'datetime',
        'ingresos_mensuales' => 'decimal:2',
        'egresos_mensuales' => 'decimal:2',
    ];

    public function paisNacimiento()
    {
        return $this->belongsTo(Pais::class, 'pais_nacimiento_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
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

    public function documentos()
    {
        return $this->hasMany(ClienteDocumento::class);
    }

    public function referencias()
    {
        return $this->hasMany(ClienteReferencia::class);
    }

    public function vinculos()
    {
        return $this->hasMany(ClienteVinculo::class);
    }

    public function vinculosEntrantes()
    {
        return $this->hasMany(ClienteVinculo::class, 'cliente_vinculado_id');
    }

    public function garantias()
    {
        return $this->hasMany(ClienteGarantia::class);
    }

    public function consentimientosSic()
    {
        return $this->hasMany(ClienteConsentimientoSic::class);
    }

    public function documentosGenerados()
    {
        return $this->hasMany(DocumentoGenerado::class);
    }
}
