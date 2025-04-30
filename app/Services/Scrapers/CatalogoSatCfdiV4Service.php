<?php

namespace App\Services\Scrapers;

use Carbon\Carbon;
use App\Models\RegimenFiscal;
use App\Services\ConsoleService;
use Illuminate\Support\Facades\Log;

use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Reader\XLSX\Options;

use App\Interfaces\BrowserClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CatalogoSatCfdiV4Service extends BaseCatalogScraperService
{
    private ConsoleService $consoleService;

    private const SAT_URL = 'http://omawww.sat.gob.mx/tramitesyservicios/Paginas/anexo_20.htm';

    public function __construct(
        HttpClientInterface $http,
        BrowserClientInterface $browserClient,
        ConsoleService $consoleService
    ) {
        parent::__construct($http, $browserClient, self::SAT_URL, 'app/sat_cfdi_v4');

        $this->consoleService = $consoleService;
    }

    /**
     * Proceso principal para descargar y procesar el catálogo CFDI V4 del SAT
     */
    public function downloadAndProcessCatalog()
    {
        try {
            Log::info('Iniciando descarga del catálogo CFDI V4 del SAT');
            Log::channel('stderr')->info('Iniciando descarga del catálogo CFDI V4 del SAT');

            // Obtener la información del catálogo CFDI V4 del SAT
            $catalogInfo = $this->getCatalogInfo();

            // Obtener la fecha de última actualización y verificar si necesitamos actualizar
            $lastUpdateDate = $catalogInfo['last_update_date']; // YYYYMMDD
            $lastUpdateDateFormatted = Carbon::createFromFormat('Ymd', $lastUpdateDate);

            if (!$this->needsUpdate($lastUpdateDateFormatted)) {
                Log::info('El catálogo CFDI V4 del SAT ya está actualizado. Última actualización: ' . $lastUpdateDate);
                Log::channel('stderr')->info('El catálogo CFDI V4 del SAT ya está actualizado. Última actualización: ' . $lastUpdateDate);

                return false;
            }

            // Descargar el archivo
            $filePath = $this->downloadCatalog($catalogInfo['download_link']);

            // Procesar el archivo XLS
            $this->processFile($filePath, $lastUpdateDateFormatted);

            Log::info('Catálogo CFDI V4 del SAT procesado correctamente');
            Log::channel('stderr')->info('Catálogo CFDI V4 del SAT procesado correctamente');

            return true;
        } catch (\Exception $e) {
            Log::error('Error al procesar el catálogo CFDI V4 del SAT: ' . $e->getMessage());
            Log::channel('stderr')->error('Error al procesar el catálogo CFDI V4 del SAT: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Verifica si necesitamos actualizar el catálogo
     */
    protected function needsUpdate(Carbon $lastUpdateDate)
    {
        if ($lastUpdateDate === null) {
            return true;
        }

        // Verificar si existe un archivo de registro de la última actualización
        $lastUpdateFile = $this->downloadPath . '/last_update.txt';

        if (!file_exists($lastUpdateFile)) {
            return true;
        }

        $storedDate = Carbon::createFromFormat('Y-m-d', trim(file_get_contents($lastUpdateFile)));

        return $lastUpdateDate->gt($storedDate);
    }

    /**
     * Obtiene la información sobre el catálogo CFDI V4.0
     * 
     * @return array Información sobre el catalogo CFDI V4, incluyendo el enlace y la fecha de la ultima actualización en el SAT
     * @throws \Exception
     */
    public function getCatalogInfo(): array
    {
        try {
            // Visitar la página del SAT
            $crawler = $this->browserClient->request('GET', $this->url);

            // Seleccionar la primera tabla del contenido
            $table = $crawler->filter('#content > div > div > table');

            if ($table->count() === 0) {
                throw new \Exception('No se encontró la tabla esperada en la página del SAT');
            }

            // Obtener todos los enlaces de la tabla
            $links = $table->filter('a');

            if ($links->count() === 0) {
                throw new \Exception('No se encontraron enlaces en la tabla');
            }

            $catalogLink = null;
            $catalogFilename = null;

            // Iterar sobre los enlaces para encontrar el que coincide con el patrón
            $links->each(function ($link) use (&$catalogLink, &$catalogFilename) {
                $href = $link->attr('href');

                // Verificar si el enlace coincide con el patrón
                if (preg_match('/documentos\/catCFDI_V_4_(\d{8})\.xls/', $href, $matches)) {
                    $catalogLink = $href;
                    $catalogFilename = basename($href);
                }
            });

            if (!$catalogLink) {
                throw new \Exception('No se encontró el enlace del catálogo CFDI V4.0');
            }

            // Asegurarnos que el enlace sea absoluto
            if (!filter_var($catalogLink, FILTER_VALIDATE_URL)) {
                // Si el enlace es relativo, construir la URL completa
                if (strpos($catalogLink, 'http') !== 0) {
                    // Si el enlace comienza con '/'
                    if (strpos($catalogLink, '/') === 0) {
                        $parsedUrl = parse_url($this->url);
                        $catalogLink = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $catalogLink;
                    } else {
                        // Si el enlace es relativo a la página actual
                        $catalogLink = dirname($this->url) . '/' . $catalogLink;
                    }
                }
            }

            Log::info("Información del catálogo CFDI V4.0 obtenida con éxito.");
            Log::channel('stderr')->info("Información del catálogo CFDI V4.0 obtenida con éxito.");

            // Obtener la fecha de la actualización
            $lastUpdateDate = preg_match('/(\d{8})/', (string) $catalogFilename, $matches) ? $matches[1] : null;

            return [
                'download_link' => $catalogLink,
                'last_update_date' => $lastUpdateDate
            ];
        } catch (\Exception $e) {
            Log::error("Error al obtener la información del catálogo CFDI: " . $e->getMessage());
            Log::channel('stderr')->error("Error al obtener la información del catálogo CFDI: " . $e->getMessage());
            throw $e;
        }
    }

    protected function downloadCatalog($url)
    {
        try {
            // Realizamos la petición POST para descargar
            $filePath = $this->downloadPath . '/' . basename($url);

            if (file_exists($filePath) && filesize($filePath) > 0) {
                Log::info('Archivo XLS ya descargado anteriormente: ' . $filePath);
                Log::channel('stderr')->info('Archivo XLS ya descargado anteriormente: ' . $filePath);
                return $filePath;
            }

            $response = $this->http->request('GET', $url);

            // Responses are lazy: this code is executed as soon as headers are received
            if (200 !== $response->getStatusCode()) {
                $response->getContent(); // this method throws an appropriate exception
            }

            // get the response content in chunks and save them in a file
            // response chunks implement Symfony\Contracts\HttpClient\ChunkInterface
            $fileHandler = fopen($filePath, 'w');
            foreach ($this->http->stream($response) as $chunk) {
                fwrite($fileHandler, $chunk->getContent());
            }

            if (file_exists($filePath) && filesize($filePath) > 0) {
                Log::info('Archivo XLS descargado correctamente: ' . $filePath);
                Log::channel('stderr')->info('Archivo XLS descargado correctamente: ' . $filePath);
                return $filePath;
            } else {
                throw new \Exception('Error al descargar el archivo XLS o archivo vacío');
            }
        } catch (\Exception $e) {
            Log::error('Error al descargar el archivo XLS: ' . $e->getMessage());
            Log::channel('stderr')->error('Error al descargar el archivo XLS: ' . $e->getMessage());
            throw new \Exception('Error al descargar el archivo XLS: ' . $e->getMessage());
        }
    }

    /**
     * Procesa el archivo XLS descargado
     */
    protected function processFile($filePath, $lastUpdateDate)
    {
        try {
            // First, we need to convert XLS to XLSX, this is done with a command line tool
            $xlsxFilePath = $filePath . 'x';

            $filePathNormalized = $this->consoleService->normalizePath($filePath)['normalized'];
            $downloadPathNormalized = $this->consoleService->normalizePath($this->downloadPath . '/')['normalized'];

            $command = 'libreoffice --headless --convert-to xlsx "' . $filePathNormalized . '" --outdir "' . $downloadPathNormalized . '"';

            $resultExcecuteCommand = $this->consoleService->run($command);
            $output = $resultExcecuteCommand['output'];
            $returnCode = $resultExcecuteCommand['outputCode'];

            if ($returnCode !== 0) {
                throw new \Exception('Error al ejecutar el comando: ' . implode(' ', $output));
            }

            $timeout = 45;
            $startTime = time();

            while (!file_exists($xlsxFilePath)) {
                if (time() - $startTime > $timeout) {
                    throw new \Exception('Error al convertir el archivo XLS a XLSX, tiempo de espera agotado');
                }
                sleep(1);
            }

            Log::info('Archivo XLS convertido a XLSX correctamente, comando ejecutado: ' . implode(' ', $output));
            Log::channel('stderr')->info('Archivo XLS convertido a XLSX correctamente, comando ejecutado: ' . implode(' ', $output));

            $options = new Options();
            $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
            $reader = new Reader($options);
            $reader->open($xlsxFilePath);

            $procesedRows = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                Log::channel('stderr')->info($sheet->getName());
                if ($sheet->getName() === 'c_RegimenFiscal') {
                    foreach ($sheet->getRowIterator() as $indexRow => $row) {
                        if ($indexRow < 7) {
                            continue;
                        }

                        $cells = $row->getCells();
                        $clave = $cells[0]->getValue();
                        $descripcion = $cells[1]->getValue();
                        $fisica = mb_strtolower($cells[2]->getValue()) == 'no' ? false : true;
                        $moral = mb_strtolower($cells[3]->getValue()) == 'no' ? false : true;
                        $fecha_inicio_vigencia = $cells[4]->getValue();
                        $fecha_fin_vigencia = $cells[5]->getValue();

                        if ($fecha_fin_vigencia === null || $fecha_fin_vigencia === '') {
                            $fecha_fin_vigencia = '2099-12-31';
                        }

                        RegimenFiscal::updateOrCreate(
                            [
                                'clave' => $clave
                            ],
                            [
                                'descripcion' => $descripcion,
                                'fisica' => $fisica,
                                'moral' => $moral,
                                'fecha_inicio_vigencia' => $fecha_inicio_vigencia,
                                'fecha_fin_vigencia' => $fecha_fin_vigencia
                            ]
                        );

                        $procesedRows++;
                    }

                    break;
                }
            }

            $reader->close();

            // Guardar fecha de actualización
            file_put_contents($this->downloadPath . '/last_update.txt', $lastUpdateDate->format('Y-m-d'));

            Log::info('Archivo XLS procesado correctamente, filas procesadas: ' . $procesedRows);
            Log::channel('stderr')->info('Archivo XLS procesado correctamente, filas procesadas: ' . $procesedRows);

            return null;
        } catch (\Exception $e) {
            Log::error('Error al procesar el archivo XLS: ' . $e->getMessage());
            Log::channel('stderr')->error('Error al procesar el archivo XLS: ' . $e->getMessage());
            throw new \Exception('Error al procesar el archivo XLS: ' . $e->getMessage());
        }
    }
}
