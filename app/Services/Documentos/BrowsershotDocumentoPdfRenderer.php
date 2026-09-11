<?php

namespace App\Services\Documentos;

use App\Contracts\DocumentoPdfRenderer;
use Illuminate\Validation\ValidationException;
use Spatie\Browsershot\Browsershot;

final class BrowsershotDocumentoPdfRenderer implements DocumentoPdfRenderer
{
    public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null, array $options = []): string
    {
        $css = $this->styles();
        $header = $this->section($headerHtml ?? '', $css);
        $footer = $this->section($footerHtml ?? '', $css, true);
        $measure = '<style>'.$css.'.section{width:174mm;display:flow-root}</style><div class="section document-content" id="header">'.($headerHtml ?? '').'</div><div class="section document-content" id="footer">'.($footerHtml ?? '').'<div style="font-size:8pt">1 / 1</div></div>';
        $sizes = json_decode($this->browser($measure)->timeout(10)->evaluate('(async () => { await document.fonts.ready; await Promise.all(Array.from(document.images).map(i => i.decode().catch(() => {}))); return JSON.stringify(["header", "footer"].map(id => document.getElementById(id).getBoundingClientRect().height * 25.4 / 96)); })()'), true, flags: JSON_THROW_ON_ERROR);
        if (max($sizes) > 60) {
            throw ValidationException::withMessages(['encabezado_html' => 'El encabezado o el pie supera 60 mm de altura. Reduzca el texto o el tamaño de las imágenes.']);
        }
        $watermark = e($options['marca_agua'] ?? '');
        $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>'.$css.'
            *{box-sizing:border-box} body{margin:0} @page{size:A4}
            .watermark{position:fixed;top:40%;left:0;width:100%;text-align:center;transform:rotate(-35deg);font:48pt "Document Sans";color:rgba(100,116,139,.10);overflow-wrap:anywhere;z-index:0;pointer-events:none}
            .document-content{position:relative;z-index:1}
        </style></head><body><div class="watermark">'.$watermark.'</div><main class="document-content">'.$bodyHtml.'</main></body></html>';

        return $this->browser($html)->format('A4')->showBackground()
            ->margins(max(26, $sizes[0] + 12), 18, max(24, $sizes[1] + 12), 18)
            ->showBrowserHeaderAndFooter()->headerHtml($header)->footerHtml($footer)->pdf();
    }

    private function browser(string $html): Browsershot
    {
        $browser = Browsershot::html($html)->writeOptionsToFile()->timeout(min(35, (int) config('documentos.renderer_timeout', 55)))
            // Header/footer fonts may not occur in the body. Load all local faces before printing.
            ->waitForFunction('Promise.all(Array.from(document.fonts, font => font.load())).then(() => true)', timeout: 10000)
            ->setOption('args', ['--host-resolver-rules=MAP * 0.0.0.0, EXCLUDE localhost']);
        foreach (['node_binary' => 'setNodeBinary', 'npm_binary' => 'setNpmBinary', 'node_modules_path' => 'setNodeModulePath', 'chrome_path' => 'setChromePath'] as $key => $method) {
            if (filled(config('documentos.'.$key))) {
                $browser->$method(config('documentos.'.$key));
            }
        }

        return $browser;
    }

    private function styles(): string
    {
        $css = file_get_contents(resource_path('css/document-content.css'));
        foreach (['Sans', 'Serif', 'Mono'] as $family) {
            foreach (['Regular' => [400, 'normal'], 'Bold' => [700, 'normal'], 'Italic' => [400, 'italic'], 'BoldItalic' => [700, 'italic']] as $variant => [$weight, $style]) {
                $font = file_get_contents(public_path("fonts/liberation/Liberation{$family}-{$variant}.ttf"));
                $css .= '@font-face{font-family:"Document '.$family.'";font-weight:'.$weight.';font-style:'.$style.';src:url(data:font/ttf;base64,'.base64_encode($font).')}';
            }
        }

        return $css;
    }

    private function section(string $html, string $css, bool $footer = false): string
    {
        return '<style>'.$css.'</style><div class="document-content" style="width:174mm;margin:0 auto">'.$html.($footer ? '<div style="font-size:8pt;text-align:right"><span class="pageNumber"></span> / <span class="totalPages"></span></div>' : '').'</div>';
    }
}
