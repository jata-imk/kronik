<?php

namespace App\Models;

use App\Enums\DocumentoPlantillaTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentoPlantilla extends Model
{
    protected $table = 'documento_plantillas';

    protected $guarded = [];

    protected $casts = [
        'tipo' => DocumentoPlantillaTipo::class,
        'activa' => 'boolean',
    ];

    public function versiones(): HasMany
    {
        return $this->hasMany(DocumentoPlantillaVersion::class);
    }
}
