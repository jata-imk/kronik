<?php

namespace App\Services\Documentos;

use App\Contracts\DocumentoPdfRenderer;
use Spatie\Browsershot\Browsershot;

final class BrowsershotDocumentoPdfRenderer implements DocumentoPdfRenderer
{
    public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null): string
    {
        $html = <<<'HTML'
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { size: A4; margin: 26mm 18mm 24mm; }
    * { box-sizing: border-box; }
    body { color: #172033; font: 11pt/1.55 Arial, Helvetica, sans-serif; margin: 0; }
    h1 { color: #13213c; font-size: 20pt; line-height: 1.2; margin: 0 0 16pt; }
    h2 { color: #243553; font-size: 15pt; margin: 18pt 0 8pt; }
    h3 { font-size: 12pt; margin: 14pt 0 6pt; }
    p { margin: 0 0 9pt; orphans: 3; widows: 3; }
    ul, ol { margin: 0 0 9pt 20pt; }
    blockquote { border-left: 3px solid #f47b5f; color: #475569; margin: 12pt 0; padding: 4pt 12pt; }
    .ql-align-center { text-align: center; } .ql-align-right { text-align: right; }
    .ql-align-justify { text-align: justify; } .ql-size-small { font-size: 9pt; }
    .ql-size-large { font-size: 14pt; } .ql-size-huge { font-size: 18pt; }
</style>
</head>
<body>{{BODY}}</body>
</html>
HTML;
        $html = str_replace('{{BODY}}', $bodyHtml, $html);
        $browser = Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->timeout((int) config('documentos.renderer_timeout', 55))
            ->setOption('args', ['--host-resolver-rules=MAP * 0.0.0.0, EXCLUDE localhost']);

        if (filled(config('documentos.node_binary'))) {
            $browser->setNodeBinary(config('documentos.node_binary'));
        }

        if (filled(config('documentos.npm_binary'))) {
            $browser->setNpmBinary(config('documentos.npm_binary'));
        }

        if (filled(config('documentos.node_modules_path'))) {
            $browser->setNodeModulePath(config('documentos.node_modules_path'));
        }

        if (filled(config('documentos.chrome_path'))) {
            $browser->setChromePath(config('documentos.chrome_path'));
        }

        if (filled($headerHtml) || filled($footerHtml)) {
            $browser->showBrowserHeaderAndFooter()
                ->headerHtml($this->chromeSection($headerHtml ?? ''))
                ->footerHtml($this->chromeSection(($footerHtml ?? '').'<span class="paginacion"><span class="pageNumber"></span> / <span class="totalPages"></span></span>'));
        }

        return $browser->pdf();
    }

    private function chromeSection(string $html): string
    {
        return '<style>body{width:100%;margin:0 18mm;color:#64748b;font:8px Arial,sans-serif}.paginacion{float:right}</style><div style="width:100%">'.$html.'</div>';
    }
}
