<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'clave',
        'domicilio',
        'telefono',
        'email',
        'horario',
        'prefijo_folio',
        'consecutivo_solicitud',
        'consecutivo_contrato',
        'consecutivo_credito',
        'consecutivo_recibo',
        'activa',
    ];

    protected $casts = [
        'domicilio' => 'array',
        'horario' => 'array',
        'activa' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }
}
