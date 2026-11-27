<?php

namespace App\Services\Documentos;

use App\Services\TemplateHtmlView;

final class DocumentoHtmlPreview
{
    public function __construct(private CompiladorPlantillaDocumento $compiler, private DocumentoRecursoService $resources, private TemplateHtmlView $view) {}

    public function render(array $content): array
    {
        $values = \App\Http\Controllers\DocumentoPlantillaController::previewValues();
        $sections = [];
        foreach (['header' => 'encabezado_html', 'body' => 'contenido_html', 'footer' => 'pie_html'] as $key => $field) {
            $sections[$key] = $this->resources->embed($this->compiler->render($content[$field] ?? '', $values));
        }

        return [...$sections, 'format' => 'html',
            'html' => $this->view->render($sections['body'], $sections['header'], $sections['footer'], $content['presentacion'] ?? [], true),
            'notice' => 'Previsualización HTML continua con datos sintéticos. No es el PDF final.'];
    }
}
