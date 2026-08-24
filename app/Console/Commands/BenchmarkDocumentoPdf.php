<?php

namespace App\Console\Commands;

use App\Contracts\DocumentoPdfRenderer;
use Illuminate\Console\Command;

class BenchmarkDocumentoPdf extends Command
{
    protected $signature = 'documentos:benchmark-pdf {--runs=5 : Ejecuciones por fixture}';

    protected $description = 'Mide el renderer PDF con fixtures sintéticos sin datos personales';

    public function handle(DocumentoPdfRenderer $renderer): int
    {
        $runs = max(1, min(30, (int) $this->option('runs')));
        $fixtures = [
            'consentimiento_sic' => '<h1>Consentimiento SIC</h1>'.str_repeat('<p>Texto regulatorio de prueba con una cláusula numerada.</p>', 24),
            'garantia' => '<h1>Garantía prendaria</h1>'.str_repeat('<p>Descripción y obligaciones de la garantía de prueba.</p>', 30),
            'contrato' => '<h1>Contrato de crédito</h1>'.str_repeat('<h2>Cláusula</h2><p>Contenido contractual sintético para comprobar saltos de página.</p>', 36),
        ];
        $rows = [];

        foreach ($fixtures as $name => $body) {
            $times = [];
            $sizes = [];
            for ($iteration = 1; $iteration <= $runs; $iteration++) {
                $start = hrtime(true);
                $pdf = $renderer->render($body, '<p>Financiera de prueba</p>', '<p>Documento sintético</p>');
                $times[] = (hrtime(true) - $start) / 1_000_000;
                $sizes[] = strlen($pdf);
            }
            sort($times);
            $rows[] = [
                $name,
                number_format($times[0], 0).' ms',
                number_format($times[(int) floor((count($times) - 1) * .5)], 0).' ms',
                number_format($times[(int) floor((count($times) - 1) * .95)], 0).' ms',
                number_format(array_sum($sizes) / count($sizes) / 1024, 1).' KiB',
            ];
        }

        $this->table(['Fixture', 'mínimo', 'mediana', 'p95 observado', 'PDF promedio'], $rows);
        $this->info('Memoria PHP pico: '.number_format(memory_get_peak_usage(true) / 1024 / 1024, 1).' MiB. Chrome se ejecuta como proceso aislado y debe medirse en la VPS.');

        return self::SUCCESS;
    }
}
