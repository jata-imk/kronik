<?php

namespace App\Models;

use App\Enums\DocumentoGeneradoEstado;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentoGenerado extends Model
{
    use HasUuids;

    protected $table = 'documentos_generados';

    protected $guarded = [];

    protected $hidden = ['disk', 'path', 'datos_utilizados'];

    protected $casts = [
        'estado' => DocumentoGeneradoEstado::class,
        'datos_utilizados' => 'encrypted:array',
        'metadatos_variables' => 'array',
        'solicitado_en' => 'datetime',
        'generado_en' => 'datetime',
    ];

    public function version()
    {
        return $this->belongsTo(DocumentoPlantillaVersion::class, 'documento_plantilla_version_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function documentable()
    {
        return $this->morphTo();
    }
}
