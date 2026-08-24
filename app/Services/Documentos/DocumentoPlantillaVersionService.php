<?php

namespace App\Services\Documentos;

use App\Enums\DocumentoPlantillaTipo;
use App\Enums\DocumentoPlantillaVersionEstado;
use App\Models\DocumentoPlantilla;
use App\Models\DocumentoPlantillaVersion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DocumentoPlantillaVersionService
{
    public function __construct(private readonly CompiladorPlantillaDocumento $compiler) {}

    public function create(array $data, ?int $userId): DocumentoPlantilla
    {
        return DB::transaction(function () use ($data, $userId) {
            $plantilla = DocumentoPlantilla::create([
                ...Arr::only($data, ['clave', 'nombre', 'tipo', 'descripcion']),
                'creada_por' => $userId,
            ]);
            $this->createVersion($plantilla, $data, $userId, 1);

            return $plantilla->load('versiones');
        });
    }

    public function update(DocumentoPlantilla $plantilla, DocumentoPlantillaVersion $version, array $data): DocumentoPlantilla
    {
        $this->ensureOwnership($plantilla, $version);
        $this->ensureEditable($version);

        return DB::transaction(function () use ($plantilla, $version, $data) {
            $plantilla->update(Arr::only($data, ['clave', 'nombre', 'descripcion']));
            $content = $this->content($plantilla->tipo, $data);
            $version->update([...$content, 'resumen_cambios' => $data['resumen_cambios'] ?? null]);

            return $plantilla->refresh();
        });
    }

    public function duplicate(DocumentoPlantilla $plantilla, DocumentoPlantillaVersion $source, ?int $userId): DocumentoPlantillaVersion
    {
        $this->ensureOwnership($plantilla, $source);
        $source->refresh();

        return DB::transaction(function () use ($plantilla, $source, $userId) {
            $next = ((int) $plantilla->versiones()->lockForUpdate()->max('numero')) + 1;

            return $this->createVersion($plantilla, [
                'encabezado_html' => $source->encabezado_html,
                'contenido_html' => $source->contenido_html,
                'pie_html' => $source->pie_html,
                'resumen_cambios' => "Duplicada desde la versión {$source->numero}",
            ], $userId, $next);
        });
    }

    public function activate(DocumentoPlantillaVersion $version): DocumentoPlantillaVersion
    {
        $this->ensureEditable($version);
        $version->loadMissing('plantilla');
        $this->compiler->validateForType(
            $version->plantilla->tipo,
            $version->encabezado_html ?? '',
            $version->contenido_html,
            $version->pie_html ?? '',
        );

        return DB::transaction(function () use ($version) {
            DocumentoPlantillaVersion::query()
                ->where('documento_plantilla_id', $version->documento_plantilla_id)
                ->where('estado', DocumentoPlantillaVersionEstado::Activa)
                ->whereKeyNot($version->id)
                ->update(['estado' => DocumentoPlantillaVersionEstado::Retirada, 'retirada_en' => now()]);

            $version->update([
                'estado' => DocumentoPlantillaVersionEstado::Activa,
                'activada_en' => now(),
                'retirada_en' => null,
            ]);

            return $version->refresh();
        });
    }

    public function retire(DocumentoPlantillaVersion $version): DocumentoPlantillaVersion
    {
        if ($version->estado !== DocumentoPlantillaVersionEstado::Activa) {
            throw ValidationException::withMessages(['version' => 'Solo puede retirarse una versión activa.']);
        }

        $version->update(['estado' => DocumentoPlantillaVersionEstado::Retirada, 'retirada_en' => now()]);

        return $version->refresh();
    }

    private function createVersion(DocumentoPlantilla $plantilla, array $data, ?int $userId, int $number): DocumentoPlantillaVersion
    {
        return $plantilla->versiones()->create([
            ...$this->content($plantilla->tipo, $data),
            'numero' => $number,
            'estado' => DocumentoPlantillaVersionEstado::Borrador,
            'resumen_cambios' => $data['resumen_cambios'] ?? null,
            'creada_por' => $userId,
        ]);
    }

    private function content(DocumentoPlantillaTipo $type, array $data): array
    {
        $header = $this->compiler->sanitize($data['encabezado_html'] ?? '');
        $body = $this->compiler->sanitize($data['contenido_html']);
        $footer = $this->compiler->sanitize($data['pie_html'] ?? '');
        $this->compiler->validateForType($type, $header, $body, $footer);
        $canonical = json_encode(['header' => $header, 'body' => $body, 'footer' => $footer], JSON_THROW_ON_ERROR);

        return [
            'encabezado_html' => $header ?: null,
            'contenido_html' => $body,
            'pie_html' => $footer ?: null,
            'contenido_hash' => hash('sha256', $canonical),
        ];
    }

    private function ensureEditable(DocumentoPlantillaVersion $version): void
    {
        if (! $version->esEditable()) {
            throw ValidationException::withMessages([
                'version' => 'Esta versión ya está activa, retirada o fue utilizada. Cree una nueva versión.',
            ]);
        }
    }

    private function ensureOwnership(DocumentoPlantilla $plantilla, DocumentoPlantillaVersion $version): void
    {
        abort_unless($version->documento_plantilla_id === $plantilla->id, 404);
    }
}
