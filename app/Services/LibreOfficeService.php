<?php

namespace App\Services;

use App\Services\ConsoleService;
use Illuminate\Support\Facades\Log;

class LibreOfficeService
{
    public function __construct(
        protected ConsoleService $consoleService,
        protected string $outputDirectory = '/tmp' // Puedes personalizar este path o inyectarlo si quieres
    ) {}

    /**
     * Convierte un archivo XLS a XLSX usando LibreOffice.
     *
     * @param string $inputPath Ruta completa del archivo .xls
     * @return string|null Ruta del archivo convertido, o null si falla
     */
    public function convertXlsToXlsx(string $inputPath): ?string
    {
        if (!file_exists($inputPath)) {
            Log::error("Archivo no encontrado: $inputPath");
            return null;
        }

        $command = sprintf(
            'libreoffice --headless --convert-to xlsx "%s" --outdir "%s"',
            $inputPath,
            $this->outputDirectory
        );

        $normalized = $this->consoleService->normalizePath($command)['normalized'];
        $output = [];
        $returnCode = 0;

        exec($normalized, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('Error al convertir archivo con LibreOffice', [
                'command' => $normalized,
                'output' => $output,
                'code' => $returnCode
            ]);
            return null;
        }

        // Construimos la ruta del archivo convertido
        $convertedPath = $this->outputDirectory . '/' . pathinfo($inputPath, PATHINFO_FILENAME) . '.xlsx';

        return file_exists($convertedPath) ? $convertedPath : null;
    }
}
