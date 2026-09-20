<?php

namespace App\Services\Documentos;

use App\Enums\DocumentoPlantillaTipo;
use Illuminate\Validation\ValidationException;

final class CompiladorPlantillaDocumento
{
    public function __construct(
        private readonly CatalogoVariablesDocumento $catalogo,
        private readonly HtmlDocumentoSanitizer $sanitizer,
    ) {}

    /** @return array<int, string> */
    public function variables(string ...$secciones): array
    {
        preg_match_all('/\{\{\s*([a-z0-9._-]+)\s*\}\}/i', implode("\n", $secciones), $matches);

        return collect($matches[1] ?? [])->map(fn ($key) => strtolower($key))->unique()->values()->all();
    }

    public function validateForType(DocumentoPlantillaTipo $tipo, string ...$secciones): void
    {
        $catalogo = $this->catalogo->all();
        $errors = [];

        foreach ($this->variables(...$secciones) as $key) {
            if (! isset($catalogo[$key])) {
                $errors[] = "La variable {{$key}} no existe en el catálogo permitido.";
            } elseif (! in_array($tipo->value, $catalogo[$key]['tipos'], true)) {
                $errors[] = "La variable {{$key}} no está disponible para este tipo de plantilla.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['contenido_html' => $errors]);
        }
    }

    public function sanitize(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }

    public function render(string $html, array $values): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-z0-9._-]+)\s*\}\}/i',
            fn (array $match) => e((string) ($values[strtolower($match[1])] ?? '')),
            $this->sanitize($html),
        ) ?? '';
    }
}
