<?php

namespace App\Http\Controllers;

use App\Enums\DocumentoPlantillaTipo;
use App\Models\DocumentoPlantillaVersion;
use App\Services\Documentos\DocumentoPlantillaVersionService;
use App\Services\Documentos\DocumentoRenderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentoPreviewController extends Controller
{
    public function show(Request $request, DocumentoPlantillaVersion $version, DocumentoRenderService $renderer)
    {
        abort_unless($request->user()->can('read plantillas-documentos'), 403);

        return $this->pdf($version->toArray(), $renderer);
    }

    public function store(Request $request, DocumentoPlantillaVersionService $versions, DocumentoRenderService $renderer)
    {
        abort_unless($request->user()->can('read plantillas-documentos') && ($request->user()->can('create plantillas-documentos') || $request->user()->can('update plantillas-documentos')), 403);
        $data = $request->validate([
            'tipo' => ['required', Rule::enum(DocumentoPlantillaTipo::class)],
            'contenido_html' => ['required', 'string', 'max:200000'],
            'encabezado_html' => ['nullable', 'string', 'max:50000'],
            'pie_html' => ['nullable', 'string', 'max:50000'],
            'presentacion' => ['nullable', 'array:marca_agua,formato'],
            'presentacion.marca_agua' => ['nullable', 'string', 'max:80'],
        ]);

        return $this->pdf($versions->content(DocumentoPlantillaTipo::from($data['tipo']), $data), $renderer);
    }

    private function pdf(array $content, DocumentoRenderService $renderer)
    {
        try {
            $pdf = $renderer->render($content, DocumentoPlantillaController::previewValues());
            if (! str_starts_with($pdf, '%PDF-')) {
                throw new \RuntimeException('PDF inválido');
            }

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="previsualizacion.pdf"',
                'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            // Do not expose render commands, paths, HTML or images to logs or users.
            return response()->json(['message' => 'No fue posible preparar el PDF. Inténtelo nuevamente.'], 503);
        }
    }
}
