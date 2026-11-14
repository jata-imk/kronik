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
    public function __construct(private readonly CompiladorPlantillaDocumento $compiler, private readonly DocumentoRecursoService $resources) {}

    public function create(array $data, ?int $userId): DocumentoPlantilla
    {
        return DB::transaction(function () use ($data, $userId) {
            $plantilla = DocumentoPlantilla::create([
                ...Arr::only($data, ['clave', 'nombre', 'tipo', 'descripcion']),
                'creada_por' => $userId,
            ]);
            $this->createVersion($plantilla, $data, $userId, 1);
            $this->record(\App\Enums\ActivityEvent::DocumentTemplateCreated, $plantilla, \App\Models\User::find($userId));

            return $plantilla->load('versiones');
        });
    }

    public function update(DocumentoPlantilla $plantilla, DocumentoPlantillaVersion $version, array $data): DocumentoPlantilla
    {
        $this->ensureOwnership($plantilla, $version);
        $this->ensureEditable($version);

        return DB::transaction(function () use ($plantilla, $version, $data) {
            $plantilla = DocumentoPlantilla::query()->lockForUpdate()->findOrFail($plantilla->id);
            $version = $plantilla->versiones()->lockForUpdate()->findOrFail($version->id);
            $this->ensureEditable($version);
            $before = [$plantilla->only(['clave', 'nombre', 'descripcion']), $version->contenido_hash, $version->resumen_cambios];
            $plantilla->update(Arr::only($data, ['clave', 'nombre', 'descripcion']));
            $content = $this->content($plantilla->tipo, $data);
            $version->update([...$content, 'resumen_cambios' => $data['resumen_cambios'] ?? null]);
            $version->recursos()->sync(array_keys($this->resources->references($content['encabezado_html'] ?? '', $content['contenido_html'], $content['pie_html'] ?? '')));

            if ($before !== [$plantilla->only(['clave', 'nombre', 'descripcion']), $version->contenido_hash, $version->resumen_cambios]) {
                $this->record(\App\Enums\ActivityEvent::DocumentTemplateUpdated, $plantilla, auth()->user());
            }

            return $plantilla->refresh();
        });
    }

    public function duplicate(DocumentoPlantilla $plantilla, DocumentoPlantillaVersion $source, ?int $userId): DocumentoPlantillaVersion
    {
        $this->ensureOwnership($plantilla, $source);
        $source->refresh();

        return DB::transaction(function () use ($plantilla, $source, $userId) {
            $plantilla = DocumentoPlantilla::query()->lockForUpdate()->findOrFail($plantilla->id);
            $source = $plantilla->versiones()->lockForUpdate()->findOrFail($source->id);
            $next = ((int) $plantilla->versiones()->lockForUpdate()->max('numero')) + 1;

            $version = $this->createVersion($plantilla, [
                'encabezado_html' => $source->encabezado_html,
                'contenido_html' => $source->contenido_html,
                'pie_html' => $source->pie_html,
                'presentacion' => $source->presentacion,
                'resumen_cambios' => "Duplicada desde la versión {$source->numero}",
            ], $userId, $next);
            $this->record(\App\Enums\ActivityEvent::DocumentTemplateVersioned, $plantilla, \App\Models\User::find($userId), $version);

            return $version;
        });
    }

    public function activate(DocumentoPlantillaVersion $version): DocumentoPlantillaVersion
    {
        return DB::transaction(function () use ($version) {
            $plantilla = DocumentoPlantilla::query()->lockForUpdate()->findOrFail($version->documento_plantilla_id);
            $version = $plantilla->versiones()->lockForUpdate()->findOrFail($version->id);
            $this->ensureEditable($version);
            $version->loadMissing('plantilla');
            $this->content($version->plantilla->tipo, $version->toArray());
            $this->compiler->validateForType(
                $version->plantilla->tipo,
                $version->encabezado_html ?? '',
                $version->contenido_html,
                $version->pie_html ?? '',
            );

            foreach (DocumentoPlantillaVersion::query()->where('documento_plantilla_id', $version->documento_plantilla_id)
                ->where('estado', DocumentoPlantillaVersionEstado::Activa)->whereKeyNot($version->id)->lockForUpdate()->get() as $previous) {
                $previous->update(['estado' => DocumentoPlantillaVersionEstado::Retirada, 'retirada_en' => now()]);
                $this->record(\App\Enums\ActivityEvent::DocumentTemplateRetired, $plantilla, auth()->user(), $previous);
            }

            $version->update([
                'estado' => DocumentoPlantillaVersionEstado::Activa,
                'activada_en' => now(),
                'retirada_en' => null,
            ]);

            $this->record(\App\Enums\ActivityEvent::DocumentTemplateActivated, $plantilla, auth()->user(), $version);

            return $version->refresh();
        });
    }

    public function retire(DocumentoPlantillaVersion $version): DocumentoPlantillaVersion
    {
        return DB::transaction(function () use ($version) {
            $plantilla = DocumentoPlantilla::query()->lockForUpdate()->findOrFail($version->documento_plantilla_id);
            $version = $plantilla->versiones()->lockForUpdate()->findOrFail($version->id);
            if ($version->estado !== DocumentoPlantillaVersionEstado::Activa) {
                throw ValidationException::withMessages(['version' => 'Solo puede retirarse una versión activa.']);
            }

            $version->update(['estado' => DocumentoPlantillaVersionEstado::Retirada, 'retirada_en' => now()]);
            $this->record(\App\Enums\ActivityEvent::DocumentTemplateRetired, $plantilla, auth()->user(), $version);

            return $version->refresh();
        });
    }

    private function record(\App\Enums\ActivityEvent $event, DocumentoPlantilla $plantilla, ?\App\Models\User $actor, ?DocumentoPlantillaVersion $version = null): void
    {
        $metadata = ['result' => 'applied'];
        if ($version) {
            $metadata['related'] = ['type' => 'documento_plantilla_version', 'id' => $version->id];
        }
        app(\App\Services\ActivityLogService::class)->log($event, $event->label(), $plantilla, $metadata, $actor,
            $actor?->current_team_id, $actor?->current_sucursal_id, inheritActorContext: false);
    }

    private function createVersion(DocumentoPlantilla $plantilla, array $data, ?int $userId, int $number): DocumentoPlantillaVersion
    {
        $version = $plantilla->versiones()->create([
            ...$this->content($plantilla->tipo, $data),
            'numero' => $number,
            'estado' => DocumentoPlantillaVersionEstado::Borrador,
            'resumen_cambios' => $data['resumen_cambios'] ?? null,
            'creada_por' => $userId,
        ]);
        $version->recursos()->sync(array_keys($this->resources->references($version->encabezado_html ?? '', $version->contenido_html, $version->pie_html ?? '')));

        return $version;
    }

    public function content(DocumentoPlantillaTipo $type, array $data): array
    {
        $header = $this->compiler->sanitize($data['encabezado_html'] ?? '');
        $body = $this->compiler->sanitize($data['contenido_html']);
        $footer = $this->compiler->sanitize($data['pie_html'] ?? '');
        if (trim(str_replace(["\xc2\xa0", "\xe2\x80\x8b"], '', html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'))) === '') {
            throw ValidationException::withMessages(['contenido_html' => 'Escriba el contenido de la plantilla. No puede contener solamente espacios, imágenes o saltos de página.']);
        }
        $watermark = trim((string) ($data['presentacion']['marca_agua'] ?? ''));
        if (mb_strlen($watermark) > 80 || str_contains($watermark, '{{')) {
            throw ValidationException::withMessages(['presentacion.marca_agua' => 'La marca de agua admite hasta 80 caracteres de texto, sin variables.']);
        }
        $presentation = ['formato' => 2, 'marca_agua' => $watermark];
        $this->compiler->validateForType($type, $header, $body, $footer);
        $canonical = json_encode(['header' => $header, 'body' => $body, 'footer' => $footer, 'presentacion' => $presentation, 'recursos' => $this->resources->references($header, $body, $footer)], JSON_THROW_ON_ERROR);

        return [
            'encabezado_html' => $header ?: null,
            'contenido_html' => $body,
            'pie_html' => $footer ?: null,
            'contenido_hash' => hash('sha256', $canonical),
            'presentacion' => $presentation,
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
