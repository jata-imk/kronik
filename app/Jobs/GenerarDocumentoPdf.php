<?php

namespace App\Jobs;

use App\Contracts\DocumentoPdfRenderer;
use App\Enums\ActivityEvent;
use App\Enums\DocumentoGeneradoEstado;
use App\Models\DocumentoGenerado;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Documentos\CompiladorPlantillaDocumento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerarDocumentoPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 55;

    public function __construct(public readonly string $documentoId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("documento:{$this->documentoId}"))->releaseAfter(10)->expireAfter(80)];
    }

    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(DocumentoPdfRenderer $renderer, CompiladorPlantillaDocumento $compiler, ActivityLogService $activity): void
    {
        $document = DocumentoGenerado::query()->with(['version.plantilla', 'cliente'])->findOrFail($this->documentoId);
        if ($document->estado === DocumentoGeneradoEstado::Generado && $document->path && Storage::disk($document->disk)->exists($document->path)) {
            return;
        }

        $document->update(['estado' => DocumentoGeneradoEstado::Procesando, 'error_codigo' => null, 'error_mensaje' => null]);
        $values = $document->datos_utilizados;
        $body = $compiler->render($document->version->contenido_html, $values);
        $header = $compiler->render($document->version->encabezado_html ?? '', $values);
        $footer = $compiler->render($document->version->pie_html ?? '', $values);
        $pdf = $renderer->render($body, $header, $footer);
        if (! str_starts_with($pdf, '%PDF-')) {
            throw new \RuntimeException('El motor no produjo un PDF válido.');
        }

        $disk = config('documentos.disk', 'local');
        $path = "documentos-generados/{$document->cliente_id}/{$document->id}.pdf";
        Storage::disk($disk)->put($path, $pdf);
        $name = Str::slug($document->version->plantilla->nombre)."-v{$document->version->numero}-{$document->id}.pdf";
        $document->update([
            'estado' => DocumentoGeneradoEstado::Generado,
            'disk' => $disk,
            'path' => $path,
            'nombre_archivo' => $name,
            'mime_type' => 'application/pdf',
            'tamano_bytes' => strlen($pdf),
            'archivo_hash' => hash('sha256', $pdf),
            'generado_en' => now(),
        ]);

        $activity->log(
            ActivityEvent::DocumentGenerated,
            'Documento generado',
            $document->cliente,
            ['related' => ['type' => 'documento_generado', 'id' => $document->id], 'state' => DocumentoGeneradoEstado::Generado->value],
            $document->creado_por ? User::find($document->creado_por) : null,
        );
    }

    public function failed(Throwable $exception): void
    {
        $document = DocumentoGenerado::query()->with('cliente')->find($this->documentoId);
        if (! $document) {
            return;
        }
        $document->update([
            'estado' => DocumentoGeneradoEstado::Fallido,
            'error_codigo' => class_basename($exception),
            'error_mensaje' => 'No fue posible generar el documento. Puede volver a intentarlo.',
        ]);
        app(ActivityLogService::class)->log(
            ActivityEvent::DocumentGenerationFailed,
            'Falló la generación de documento',
            $document->cliente,
            ['related' => ['type' => 'documento_generado', 'id' => $document->id], 'state' => DocumentoGeneradoEstado::Fallido->value],
            $document->creado_por ? User::find($document->creado_por) : null,
        );
    }
}
