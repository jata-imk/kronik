<?php

namespace App\Services\Scrapers;

use Symfony\Component\HttpClient\HttpClient;
use App\Services\Scrapers\BrowserClientService;

use App\Models\CodigoPostal;
use App\Models\DivisionAdministrativa;
use App\Models\Pais;

use Carbon\Carbon;
use ZipArchive;

use Illuminate\Support\Facades\Log;

class SepomexService extends BaseCatalogScraperService
{
    protected $url = 'https://www.correosdemexico.gob.mx/SSLServicios/ConsultaCP/CodigoPostal_Exportar.aspx';
    protected $downloadPath = 'app/sepomex';
    protected $extractPath = 'app/sepomex/extracted';

    public function __construct()
    {
        $http = HttpClient::create([
            'timeout' => 60,
            'verify_host' => false,
        ]);
        $browserClient = new BrowserClientService($http);

        parent::__construct($http, $browserClient);

        $this->extractPath = $this->getOrCreatePath($this->extractPath);
    }

    /**
     * Proceso principal para descargar y procesar el catálogo SEPOMEX
     */
    public function downloadAndProcessCatalog()
    {
        try {
            Log::info('Iniciando descarga del catálogo SEPOMEX');
            Log::channel('stderr')->info('Iniciando descarga del catálogo SEPOMEX');

            // Obtener la fecha de última actualización y verificar si necesitamos actualizar
            $lastUpdateDate = $this->getLastUpdateDate();

            if (!$this->needsUpdate($lastUpdateDate)) {
                Log::info('El catálogo SEPOMEX ya está actualizado. Última actualización: ' . $lastUpdateDate);
                Log::channel('stderr')->info('El catálogo SEPOMEX ya está actualizado. Última actualización: ' . $lastUpdateDate);
                return false;
            }

            // Descargar el archivo
            $zipFilePath = $this->downloadCatalog();

            // // Extraer el archivo
            $txtFilePath = $this->extractZipFile($zipFilePath);

            // Procesar el archivo TXT
            $this->processFile($txtFilePath, $lastUpdateDate);

            Log::info('Catálogo SEPOMEX procesado correctamente');
            Log::channel('stderr')->info('Catálogo SEPOMEX procesado correctamente');
            return true;
        } catch (\Exception $e) {
            Log::error('Error al procesar el catálogo SEPOMEX: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene la fecha de última actualización del catálogo
     */
    protected function getLastUpdateDate()
    {
        try {
            $crawler = $this->browserClient->request('GET', $this->url);
            $updateText = $crawler->filter('#lblfec')->text();

            // Extraer la fecha del texto "Última Actualización de Información: Marzo 17 de 2025"
            preg_match('/Última Actualización de Información: (.+)/', $updateText, $matches);
            $dateString = $matches[1] ?? '';

            // Convertir fecha a formato Carbon
            $months = [
                'Enero' => '01',
                'Febrero' => '02',
                'Marzo' => '03',
                'Abril' => '04',
                'Mayo' => '05',
                'Junio' => '06',
                'Julio' => '07',
                'Agosto' => '08',
                'Septiembre' => '09',
                'Octubre' => '10',
                'Noviembre' => '11',
                'Diciembre' => '12'
            ];

            preg_match('/(\w+) (\d+) de (\d+)/', $dateString, $dateParts);
            if (count($dateParts) >= 4) {
                $month = $months[$dateParts[1]] ?? '01';
                $day = str_pad($dateParts[2], 2, '0', STR_PAD_LEFT);
                $year = $dateParts[3];

                return Carbon::createFromFormat('Y-m-d', "$year-$month-$day");
            }

            Log::warning('No se pudo parsear la fecha de actualización: ' . $dateString);
            return null;
        } catch (\Exception $e) {
            Log::error('Error al obtener fecha de actualización: ' . $e->getMessage());
            throw new \Exception('Error al obtener la fecha de actualización del catálogo SEPOMEX: ' . $e->getMessage());
        }
    }

    /**
     * Verifica si necesitamos actualizar el catálogo
     */
    protected function needsUpdate(Carbon $lastUpdateDate = null)
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
     * Descarga el catálogo de SEPOMEX
     */
    protected function downloadCatalog()
    {
        try {
            // Obtenemos la página y sus tokens
            $crawler = $this->browserClient->request('GET', $this->url);

            // Obtenemos los campos ocultos necesarios para el POST
            $form = $crawler->filter('form')->form();
            $formValues = $form->getValues();

            // Modificamos el valor para seleccionar el formato TXT
            $formValues['rblTipo'] = 'txt';

            // Preparamos para el envío
            $viewstate = $formValues['__VIEWSTATE'];
            $eventvalidation = $formValues['__EVENTVALIDATION'];

            // Realizamos la petición POST para descargar
            $zipFilePath = $this->downloadPath . '/sepomex_' . date('Ymd_His') . '.zip';

            $response = $this->http->request('POST', $this->url, [
                'body' => [
                    '__VIEWSTATE' => $viewstate,
                    '__EVENTVALIDATION' => $eventvalidation,
                    'rblTipo' => 'txt',
                    'btnDescarga.x' => '54',
                    'btnDescarga.y' => '13'
                ]
            ]);

            // Responses are lazy: this code is executed as soon as headers are received
            if (200 !== $response->getStatusCode()) {
                $response->getContent(); // this method throws an appropriate exception
            }

            // get the response content in chunks and save them in a file
            // response chunks implement Symfony\Contracts\HttpClient\ChunkInterface
            $fileHandler = fopen($zipFilePath, 'w');
            foreach ($this->http->stream($response) as $chunk) {
                fwrite($fileHandler, $chunk->getContent());
            }

            if (file_exists($zipFilePath) && filesize($zipFilePath) > 0) {
                Log::info('Archivo ZIP descargado correctamente: ' . $zipFilePath);
                Log::channel('stderr')->info('Archivo ZIP descargado correctamente: ' . $zipFilePath);
                return $zipFilePath;
            } else {
                throw new \Exception('Error al descargar el archivo ZIP o archivo vacío');
            }
        } catch (\Exception $e) {
            Log::error('Error al descargar catálogo: ' . $e->getMessage());
            throw new \Exception('Error al descargar el catálogo SEPOMEX: ' . $e->getMessage());
        }
    }

    /**
     * Extrae el archivo ZIP descargado
     */
    protected function extractZipFile($zipFilePath)
    {
        try {
            $zip = new ZipArchive;

            if ($zip->open($zipFilePath) === TRUE) {
                $zip->extractTo($this->extractPath);
                $zip->close();

                // Buscar el archivo TXT
                $files = glob($this->extractPath . '/*.txt');

                if (count($files) > 0) {
                    Log::info('Archivo TXT extraído correctamente: ' . $files[0]);
                    Log::channel('stderr')->info('Archivo TXT extraído correctamente: ' . $files[0]);
                    return $files[0];
                } else {
                    throw new \Exception('No se encontró archivo TXT dentro del ZIP');
                }
            } else {
                throw new \Exception('No se pudo abrir el archivo ZIP');
            }
        } catch (\Exception $e) {
            Log::error('Error al extraer archivo ZIP: ' . $e->getMessage());
            throw new \Exception('Error al extraer el archivo ZIP: ' . $e->getMessage());
        }
    }

    /**
     * Procesa el archivo TXT descargado
     */
    protected function processFile($txtFilePath, $lastUpdateDate)
    {
        try {
            // Leer el archivo
            $fileContent = iconv('ISO-8859-1', 'UTF-8', file_get_contents($txtFilePath));

            // Dividir por líneas
            $lines = explode("\n", $fileContent);

            // Procesar registros
            $total = count($lines);

            $ids = [];
            $paisId = Pais::where('codigo_iso', 'MX')->first()->id;

            foreach ($lines as $index => $line) {
                if (empty(trim($line)) || $index < 2) continue;

                // Los campos están separados por |
                $fields = explode('|', $line);

                if (count($fields) < 15) continue; // Verificar que tenga todos los campos
                // Mapear campos según la documentación de SEPOMEX
                // d_codigo: Código postal
                // d_asenta: Nombre del asentamiento
                // d_tipo_asenta: Tipo de asentamiento
                // d_mnpio: Municipio
                // d_estado: Estado
                // d_ciudad: Ciudad
                // [ ] - En total 15 campos
                $codigoPostalAsentamiento = $fields[0];
                $nombreAsentamiento = $fields[1];
                $tipoAsentamiento = $fields[2];
                $nombreMunicipio = $fields[3];
                $nombreEntidad = $fields[4];
                $nombreCiudad = $fields[5];
                $codigoPostalAdministracionPostal = $fields[6]; // $fields[6] y $fields[8] son el mismo
                $claveEntidad = $fields[7];
                // $codigoPostalAdministracionPostal = $fields[8];
                // $fields[9] es un campo vacio
                $claveTipoAsentamiento = $fields[10];
                $claveMunicipio = $fields[11];
                $identificadorUnicoAsentamiento = $fields[12]; // Nivel municipal
                $zonaAsentamiento = $fields[13]; // Rural o urbano
                $claveCiudad = trim($fields[14]);

                // Aquí implementarías la lógica para guardar en tu base de datos
                if (!isset($ids[$claveEntidad])) {
                    $division = DivisionAdministrativa::updateOrCreate(
                        ['division_padre_id' => null, 'codigo' => $claveEntidad, 'pais_id' => $paisId],
                        ['nombre' => $nombreEntidad, 'nivel' => 1, 'tipo' => 'estado']
                    );

                    $ids[$claveEntidad] = [
                        'id' => $division->id,
                        'municipios' => [],
                    ];
                }

                if (!isset($ids[$claveEntidad]['municipios'][$claveMunicipio])) {
                    $division = DivisionAdministrativa::updateOrCreate(
                        ['division_padre_id' => $ids[$claveEntidad]['id'], 'codigo' => $claveMunicipio],
                        ['pais_id' => $paisId, 'nombre' => $nombreMunicipio, 'nivel' => 2, 'tipo' => 'municipio',]
                    );

                    $ids[$claveEntidad]['municipios'][$claveMunicipio] = $division->id;
                }

                $division = DivisionAdministrativa::updateOrCreate(
                    ['division_padre_id' => $ids[$claveEntidad]['municipios'][$claveMunicipio], 'codigo' => $identificadorUnicoAsentamiento],
                    ['pais_id' => $paisId, 'nombre' => $nombreAsentamiento, 'nivel' => 3, 'tipo' => $tipoAsentamiento]
                );

                $datosAdicionales = [
                    'clave_tipo_asentamiento' => $claveTipoAsentamiento,
                    'zona_asentamiento' => $zonaAsentamiento,
                ];

                if ($claveCiudad !== '') {
                    $datosAdicionales['clave_ciudad'] = $claveCiudad;
                    $datosAdicionales['nombre_ciudad'] = $nombreCiudad;
                }

                CodigoPostal::updateOrCreate(
                    ['pais_id' => $paisId, 'codigo' => $codigoPostalAsentamiento, 'division_admin_id' => $division->id],
                    ['datos_adicionales' => $datosAdicionales]
                );

                // Cada 1000 registros, informamos el progreso
                if ($index % 1000 === 0) {
                    Log::info("Procesando catálogo SEPOMEX: $index/$total registros");
                    Log::channel('stderr')->info("Procesando catálogo SEPOMEX: $index/$total registros");
                }

                $lines[$index] = null;
            }

            // Guardar fecha de actualización
            file_put_contents($this->downloadPath . '/last_update.txt', $lastUpdateDate->format('Y-m-d'));

            Log::info("Catálogo SEPOMEX procesado: $total registros en total");
            Log::channel('stderr')->info("Catálogo SEPOMEX procesado: $total registros en total");

            return $total;
        } catch (\Exception $e) {
            Log::error('Error al procesar archivo TXT: ' . $e->getMessage());
            throw new \Exception('Error al procesar el archivo TXT: ' . $e->getMessage());
        }
    }
}
