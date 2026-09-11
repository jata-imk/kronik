<?php

use App\Services\Documentos\BrowsershotDocumentoPdfRenderer;

test('Chromium renderiza fuentes locales encabezados y páginas', function () {
    config(['documentos.node_binary' => null, 'documentos.npm_binary' => null, 'documentos.chrome_path' => null, 'documentos.node_modules_path' => base_path('node_modules')]);
    $pdf = app(BrowsershotDocumentoPdfRenderer::class)->render(
        '<h1>Prueba áéíóú</h1><div class="document-page-break"></div><p>Segunda página</p>',
        '<p>Encabezado</p>',
        '<p>Pie</p>',
        ['marca_agua' => 'PRUEBA'],
    );

    expect($pdf)->toStartWith('%PDF-')->and(strlen($pdf))->toBeGreaterThan(1000);
})->skip(getenv('DOCUMENT_RENDERER_INTEGRATION') !== '1', 'Requiere Chromium; habilitar DOCUMENT_RENDERER_INTEGRATION=1.');
