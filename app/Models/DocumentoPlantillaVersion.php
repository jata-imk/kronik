<?php

namespace App\Models;

use App\Enums\DocumentoPlantillaVersionEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class DocumentoPlantillaVersion extends Model
{
    private const IMMUTABLE_FIELDS = [
        'encabezado_html', 'contenido_html', 'pie_html', 'contenido_hash',
    ];

    protected $table = 'documento_plantilla_versiones';

    protected $guarded = [];

    protected $casts = [
        'estado' => DocumentoPlantillaVersionEstado::class,
        'activada_en' => 'datetime',
        'retirada_en' => 'datetime',
    ];

    public function plantilla()
    {
        return $this->belongsTo(DocumentoPlantilla::class, 'documento_plantilla_id');
    }

    public function documentosGenerados()
    {
        return $this->hasMany(DocumentoGenerado::class, 'documento_plantilla_version_id');
    }

    public function esEditable(): bool
    {
        return $this->estado === DocumentoPlantillaVersionEstado::Borrador
            && ! $this->documentosGenerados()->exists();
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $eraBorrador = $version->getRawOriginal('estado') === DocumentoPlantillaVersionEstado::Borrador->value;
            if ((! $eraBorrador || $version->documentosGenerados()->exists()) && $version->isDirty(self::IMMUTABLE_FIELDS)) {
                throw ValidationException::withMessages([
                    'version' => 'Una versión activa, retirada o utilizada no puede modificarse. Cree una nueva versión.',
                ]);
            }
        });

        static::deleting(function (self $version): void {
            if (! $version->esEditable()) {
                throw ValidationException::withMessages([
                    'version' => 'Una versión activa, retirada o utilizada no puede eliminarse.',
                ]);
            }
        });
    }
}
