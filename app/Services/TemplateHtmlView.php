<?php

namespace App\Services;

/** Pure presentation: no PDF service, browser, HTTP client or business writes. */
final class TemplateHtmlView
{
    public function render(string $body, string $header = '', string $footer = '', array $presentation = [], bool $document = false): string
    {
        $css = app(\App\Services\Documentos\DocumentoEstilos::class)->css($document);
        $watermark = e((string) ($presentation['marca_agua'] ?? ''));
        $csp = "default-src 'none'; script-src 'none'; style-src 'unsafe-inline'; img-src data:; font-src data:; base-uri 'none'; form-action 'none'; connect-src 'none'";

        return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="Content-Security-Policy" content="'.e($csp).'"><style>'.$css
            .'body{margin:0;padding:20px;background:white}.document-content{max-width:100%;font-family:Arial,sans-serif}.document-content img{max-width:100%;height:auto}.document-page-break{break-after:auto!important;page-break-after:auto!important}.preview-watermark{color:#64748b;opacity:.35;font-size:24px;text-align:center}header,footer{margin:12px 0}'
            .($document ? '.document-content{font-family:"Document Sans",sans-serif}' : '')
            .'</style></head><body><article class="document-content"><header>'.$header.'</header>'
            .($watermark !== '' ? '<div class="preview-watermark">'.$watermark.'</div>' : '')
            .$body.'<footer>'.$footer.'</footer></article></body></html>';
    }
}
