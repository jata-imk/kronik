<?php

namespace App\Services\Documentos;

use App\Contracts\DocumentoPdfRenderer;

final class DocumentoRenderService
{
    public function __construct(private readonly CompiladorPlantillaDocumento $compiler, private readonly DocumentoRecursoService $resources, private readonly DocumentoPdfRenderer $renderer) {}

    public function render(array $content, array $values, ?array $paquete = null): string
    {
        $sections = [];
        foreach (['contenido_html', 'encabezado_html', 'pie_html'] as $key) {
            $sections[] = $this->resources->embed($this->compiler->render($content[$key] ?? '', $values));
        }

        if ($paquete !== null) {
            $sections[0] .= view('documentos.paquete-anexo', ['snapshot' => $paquete])->render();
            $sections[1] = '<strong>Documento de prueba QA — sin validez contractual</strong>'.$sections[1];
            $content['presentacion']['marca_agua'] = 'QA — SIN VALIDEZ CONTRACTUAL';
        }

        return $this->renderer->render(...[...$sections, $content['presentacion'] ?? []]);
    }
}
