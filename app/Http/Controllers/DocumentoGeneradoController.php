<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Enums\DocumentoGeneradoEstado;
use App\Enums\DocumentoPlantillaTipo;
use App\Http\Requests\ConsultarDocumentoGeneradoRequest;
use App\Http\Requests\GenerarDocumentoRequest;
use App\Models\Cliente;
use App\Models\ClienteGarantia;
use App\Models\DocumentoGenerado;
use App\Models\DocumentoPlantillaVersion;
use App\Services\ActivityLogService;
use App\Services\Documentos\DocumentoGeneracionService;
use App\Services\Documentos\RespuestaArchivoPrivado;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DocumentoGeneradoController extends Controller
{
    use AuthorizesRequests;

    public function store(GenerarDocumentoRequest $request, Cliente $cliente, DocumentoGeneracionService $service)
    {
        $data = $request->validated();
        $version = DocumentoPlantillaVersion::query()->findOrFail($data['version_id']);
        $this->authorize('generate', $version);
        $guarantee = isset($data['garantia_id']) ? ClienteGarantia::query()->findOrFail($data['garantia_id']) : null;
        $document = $service->request($cliente, $version, $guarantee, $data['idempotency_key'], $request->user(), (bool) ($data['confirm_duplicate'] ?? false));

        return back()->with('success', $document->estado === DocumentoGeneradoEstado::Generado ? 'El documento ya estaba generado.' : 'Documento enviado a generación.');
    }

    public function existing(ConsultarDocumentoGeneradoRequest $request, Cliente $cliente, DocumentoGeneracionService $service)
    {
        $data = $request->validated();
        $version = DocumentoPlantillaVersion::query()->findOrFail($data['version_id']);
        $this->authorize('generate', $version);
        $guarantee = isset($data['garantia_id']) ? ClienteGarantia::query()->findOrFail($data['garantia_id']) : null;

        if ($version->plantilla()->value('tipo') !== DocumentoPlantillaTipo::Garantia->value) {
            $guarantee = null;
        }

        if ($guarantee && $guarantee->cliente_id !== $cliente->id) {
            abort(404);
        }

        $document = $service->findExisting($cliente, $version, $guarantee);

        if (! $document) {
            return response()->json(['documento' => null]);
        }

        $canView = $document->estado === DocumentoGeneradoEstado::Generado
            && $request->user()->can('view', $document);

        return response()->json(['documento' => [
            'id' => $document->id,
            'estado' => $document->estado->value,
            'nombre_archivo' => $document->nombre_archivo,
            'solicitado_en' => $document->solicitado_en,
            'generado_en' => $document->generado_en,
            'bloquea' => in_array($document->estado, [DocumentoGeneradoEstado::Pendiente, DocumentoGeneradoEstado::Procesando], true),
            'view_url' => $canView ? route('documentos-generados.view', $document) : null,
            'download_url' => $canView && $request->user()->can('download', $document) ? route('documentos-generados.download', $document) : null,
        ]]);
    }

    public function status(DocumentoGenerado $documento)
    {
        $this->authorize('view', $documento);

        return response()->json($documento->only(['id', 'estado', 'error_mensaje', 'generado_en']));
    }

    public function view(DocumentoGenerado $documento, RespuestaArchivoPrivado $files)
    {
        $this->authorize('view', $documento);
        $this->ensureReady($documento);
        $this->log(ActivityEvent::DocumentViewed, 'Documento visualizado', $documento);

        return $files->make($documento->disk, $documento->path, $documento->nombre_archivo);
    }

    public function download(DocumentoGenerado $documento, RespuestaArchivoPrivado $files)
    {
        $this->authorize('download', $documento);
        $this->ensureReady($documento);
        $this->log(ActivityEvent::DocumentDownloaded, 'Documento descargado', $documento);

        return $files->make($documento->disk, $documento->path, $documento->nombre_archivo, true);
    }

    private function ensureReady(DocumentoGenerado $document): void
    {
        abort_unless($document->estado === DocumentoGeneradoEstado::Generado && $document->disk && $document->path, 404);
    }

    private function log(ActivityEvent $event, string $description, DocumentoGenerado $document): void
    {
        app(ActivityLogService::class)->log($event, $description, $document->cliente, ['related' => ['type' => 'documento_generado', 'id' => $document->id]], request()->user());
    }
}
