<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class DocumentoRecurso extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['disk', 'path'];

    protected static function booted(): void
    {
        static::updating(fn () => throw ValidationException::withMessages(['archivo' => 'Las imágenes de la biblioteca son inmutables. Suba una nueva imagen.']));
        static::deleting(fn () => throw ValidationException::withMessages(['archivo' => 'Las imágenes se conservan para proteger las versiones documentales.']));
    }
}
