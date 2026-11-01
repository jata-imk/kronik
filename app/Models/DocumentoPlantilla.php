<?php

namespace App\Models;

use App\Enums\DocumentoPlantillaTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentoPlantilla extends Model
{
    protected $table = 'documento_plantillas';

    protected $guarded = [];

    protected $casts = [
        'tipo' => DocumentoPlantillaTipo::class,
        'activa' => 'boolean',
    ];

    public function ultimaVersion(): HasOne
    {
        return $this->hasOne(DocumentoPlantillaVersion::class)->latestOfMany('numero');
    }

    public function versionActiva(): HasOne
    {
        return $this->hasOne(DocumentoPlantillaVersion::class)->ofMany(['numero' => 'max'], fn ($q) => $q->where('estado', 'activa'));
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(DocumentoPlantillaVersion::class);
    }
}
