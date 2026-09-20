<?php

namespace App\Services\Documentos;

use App\Contracts\DocumentoPdfRenderer;

final class DocumentoRenderService
{
    public function __construct(private readonly CompiladorPlantillaDocumento $compiler, private readonly DocumentoRecursoService $resources, private readonly DocumentoPdfRenderer $renderer) {}

    public function render(array $content, array $values): string
    {
        $sections = [];
        foreach (['contenido_html', 'encabezado_html', 'pie_html'] as $key) {
            $sections[] = $this->resources->embed($this->compiler->render($content[$key] ?? '', $values));
        }

        return $this->renderer->render(...[...$sections, $content['presentacion'] ?? []]);
    }
}
