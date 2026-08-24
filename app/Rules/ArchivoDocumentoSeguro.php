<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ArchivoDocumentoSeguro implements ValidationRule
{
    private const TYPES = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('El archivo no pudo cargarse correctamente.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mime = strtolower((string) $value->getMimeType());
        if (! isset(self::TYPES[$extension]) || ! in_array($mime, self::TYPES[$extension], true) || ! $this->hasExpectedSignature($value, $extension)) {
            $fail('El archivo debe ser un PDF, JPG o PNG válido; su contenido no coincide con la extensión.');
        }
    }

    private function hasExpectedSignature(UploadedFile $file, string $extension): bool
    {
        $signature = file_get_contents($file->getRealPath(), false, null, 0, 12);
        if ($signature === false) {
            return false;
        }

        return match ($extension) {
            'pdf' => str_starts_with($signature, '%PDF-'),
            'jpg', 'jpeg' => str_starts_with($signature, "\xFF\xD8\xFF"),
            'png' => str_starts_with($signature, "\x89PNG\r\n\x1A\n"),
            default => false,
        };
    }
}
