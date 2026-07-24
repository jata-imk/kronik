<?php

namespace App\Services\Scrapers;

use App\Interfaces\BrowserClientInterface;
use App\Models\CodigoPostal;
use App\Models\DivisionAdministrativa;
use App\Models\Pais;
use Carbon\Carbon;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SplFileObject;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ZipArchive;

class SepomexService extends BaseCatalogScraperService
{
    private const URL_SEPOMEX = 'https://www.correosdemexico.gob.mx/SSLServicios/ConsultaCP/CodigoPostal_Exportar.aspx';

    private string $extractPath = 'app/sepomex/extracted';

    public function __construct(
        HttpClientInterface $http,
        BrowserClientInterface $browserClient
    ) {
        parent::__construct($http, $browserClient, self::URL_SEPOMEX, 'app/sepomex');

        $this->extractPath = $this->getOrCreatePath($this->extractPath);
    }

    /**
     * Proceso principal para descargar y procesar el catálogo SEPOMEX
     */
    public function downloadAndProcessCatalog()
    {
        try {
            if (CodigoPostal::query()->doesntExist() && Pais::where('codigo_iso', 'MX')->exists()) {
                $cachedFiles = glob($this->extractPath.'/*.txt');

                if (! empty($cachedFiles)) {
                    usort($cachedFiles, fn (string $left, string $right) => filemtime($right) <=> filemtime($left));
                    $this->restoreFileInBulk($cachedFiles[0], Carbon::createFromTimestamp(filemtime($cachedFiles[0])));

                    return true;
                }
            }

            Log::info('Iniciando descarga del catálogo SEPOMEX');
            Log::channel('stderr')->info('Iniciando descarga del catálogo SEPOMEX');

            $isEmptyCatalog = CodigoPostal::query()->doesntExist();

            // Obtener la fecha de última actualización y verificar si necesitamos actualizar
            $lastUpdateDate = $this->getLastUpdateDate();

            if (! $this->needsUpdate($lastUpdateDate)) {
                Log::info('El catálogo SEPOMEX ya está actualizado. Última actualización: '.$lastUpdateDate);
                Log::channel('stderr')->info('El catálogo SEPOMEX ya está actualizado. Última actualización: '.$lastUpdateDate);

                return false;
            }

            // Descargar el archivo
            $zipFilePath = $this->downloadCatalog();

            // // Extraer el archivo
            $txtFilePath = $this->extractZipFile($zipFilePath);

            // Procesar el archivo TXT
            if ($isEmptyCatalog) {
                $this->restoreFileInBulk($txtFilePath, $lastUpdateDate);
            } else {
                $this->processFile($txtFilePath, $lastUpdateDate);
            }

            Log::info('Catálogo SEPOMEX procesado correctamente');
            Log::channel('stderr')->info('Catálogo SEPOMEX procesado correctamente');

            return true;
        } catch (\Exception $e) {
            Log::error('Error al procesar el catálogo SEPOMEX: '.$e->getMessage());
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
                'Diciembre' => '12',
            ];

            preg_match('/(\w+) (\d+) de (\d+)/', $dateString, $dateParts);
            if (count($dateParts) >= 4) {
                $month = $months[$dateParts[1]] ?? '01';
                $day = str_pad($dateParts[2], 2, '0', STR_PAD_LEFT);
                $year = $dateParts[3];

                return Carbon::createFromFormat('Y-m-d', "$year-$month-$day");
            }

            Log::warning('No se pudo parsear la fecha de actualización: '.$dateString);

            return null;
        } catch (\Exception $e) {
            Log::error('Error al obtener fecha de actualización: '.$e->getMessage());
            throw new \Exception('Error al obtener la fecha de actualización del catálogo SEPOMEX: '.$e->getMessage());
        }
    }

    /**
     * Verifica si necesitamos actualizar el catálogo
     */
    protected function needsUpdate(?Carbon $lastUpdateDate = null)
    {
        if (CodigoPostal::query()->doesntExist()) {
            return true;
        }

        if ($lastUpdateDate === null) {
            return true;
        }

        // Verificar si existe un archivo de registro de la última actualización
        $lastUpdateFile = $this->downloadPath.'/last_update.txt';

        if (! file_exists($lastUpdateFile)) {
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
            $zipFilePath = $this->downloadPath.'/sepomex_'.date('Ymd_His').'.zip';

            $response = $this->http->request('POST', $this->url, [
                'body' => [
                    '__VIEWSTATE' => $viewstate,
                    '__EVENTVALIDATION' => $eventvalidation,
                    'rblTipo' => 'txt',
                    'btnDescarga.x' => '54',
                    'btnDescarga.y' => '13',
                ],
            ]);

            // Responses are lazy: this code is executed as soon as headers are received
            if ($response->getStatusCode() !== 200) {
                $response->getContent(); // this method throws an appropriate exception
            }

            // get the response content in chunks and save them in a file
            // response chunks implement Symfony\Contracts\HttpClient\ChunkInterface
            $fileHandler = fopen($zipFilePath, 'w');
            foreach ($this->http->stream($response) as $chunk) {
                fwrite($fileHandler, $chunk->getContent());
            }

            if (file_exists($zipFilePath) && filesize($zipFilePath) > 0) {
                Log::info('Archivo ZIP descargado correctamente: '.$zipFilePath);
                Log::channel('stderr')->info('Archivo ZIP descargado correctamente: '.$zipFilePath);

                return $zipFilePath;
            } else {
                throw new \Exception('Error al descargar el archivo ZIP o archivo vacío');
            }
        } catch (\Exception $e) {
            Log::error('Error al descargar catálogo: '.$e->getMessage());
            throw new \Exception('Error al descargar el catálogo SEPOMEX: '.$e->getMessage());
        }
    }

    /**
     * Extrae el archivo ZIP descargado
     */
    protected function extractZipFile($zipFilePath)
    {
        try {
            $zip = new ZipArchive;

            if ($zip->open($zipFilePath) === true) {
                $zip->extractTo($this->extractPath);
                $zip->close();

                // Buscar el archivo TXT
                $files = glob($this->extractPath.'/*.txt');

                if (count($files) > 0) {
                    Log::info('Archivo TXT extraído correctamente: '.$files[0]);
                    Log::channel('stderr')->info('Archivo TXT extraído correctamente: '.$files[0]);

                    return $files[0];
                } else {
                    throw new \Exception('No se encontró archivo TXT dentro del ZIP');
                }
            } else {
                throw new \Exception('No se pudo abrir el archivo ZIP');
            }
        } catch (\Exception $e) {
            Log::error('Error al extraer archivo ZIP: '.$e->getMessage());
            throw new \Exception('Error al extraer el archivo ZIP: '.$e->getMessage());
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
                if (empty(trim($line)) || $index < 2) {
                    continue;
                }

                // Los campos están separados por |
                $fields = explode('|', $line);

                if (count($fields) < 15) {
                    continue;
                } // Verificar que tenga todos los campos
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
                if (! isset($ids[$claveEntidad])) {
                    $division = DivisionAdministrativa::updateOrCreate(
                        ['division_padre_id' => null, 'codigo' => $claveEntidad, 'pais_id' => $paisId],
                        ['nombre' => $nombreEntidad, 'nivel' => 1, 'tipo' => 'estado']
                    );

                    $ids[$claveEntidad] = [
                        'id' => $division->id,
                        'municipios' => [],
                    ];
                }

                if (! isset($ids[$claveEntidad]['municipios'][$claveMunicipio])) {
                    $division = DivisionAdministrativa::updateOrCreate(
                        ['division_padre_id' => $ids[$claveEntidad]['id'], 'codigo' => $claveMunicipio],
                        ['pais_id' => $paisId, 'nombre' => $nombreMunicipio, 'nivel' => 2, 'tipo' => 'municipio']
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
            $updateDateString = $lastUpdateDate ? $lastUpdateDate->format('Y-m-d') : date('Y-m-d');
            file_put_contents($this->downloadPath.'/last_update.txt', $updateDateString);

            Log::info("Catálogo SEPOMEX procesado: $total registros en total");
            Log::channel('stderr')->info("Catálogo SEPOMEX procesado: $total registros en total");

            return $total;
        } catch (\Exception $e) {
            Log::error('Error al procesar archivo TXT: '.$e->getMessage());
            throw new \Exception('Error al procesar el archivo TXT: '.$e->getMessage());
        }
    }

    private function restoreFileInBulk(string $txtFilePath, ?Carbon $lastUpdateDate): int
    {
        $paisId = Pais::where('codigo_iso', 'MX')->value('id');

        if (! $paisId) {
            throw new \RuntimeException('El catálogo de países no contiene México.');
        }

        $states = [];
        $municipalities = [];

        foreach ($this->sepomexRows($txtFilePath) as $row) {
            $states[$row['state_code']] = $row['state_name'];
            $municipalities[$row['state_code'].'|'.$row['municipality_code']] = [
                'state_code' => $row['state_code'],
                'code' => $row['municipality_code'],
                'name' => $row['municipality_name'],
            ];
        }

        $processed = DB::transaction(function () use ($paisId, $states, $municipalities, $txtFilePath): int {
            DB::table('codigos_postales')->where('pais_id', $paisId)->delete();

            foreach ([3, 2, 1] as $level) {
                DB::table('divisiones_administrativas')
                    ->where('pais_id', $paisId)
                    ->where('nivel', $level)
                    ->delete();
            }

            $now = now();
            DB::table('divisiones_administrativas')->insert(
                collect($states)->map(fn (string $name, string $code) => [
                    'pais_id' => $paisId,
                    'nombre' => $name,
                    'codigo' => $code,
                    'nivel' => 1,
                    'division_padre_id' => null,
                    'tipo' => 'estado',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all(),
            );

            $stateIds = DB::table('divisiones_administrativas')
                ->where('pais_id', $paisId)
                ->where('nivel', 1)
                ->pluck('id', 'codigo');
            Log::channel('stderr')->info('SEPOMEX: estados restaurados.');

            foreach (array_chunk(array_values($municipalities), 1000) as $chunk) {
                DB::table('divisiones_administrativas')->insert(
                    array_map(fn (array $municipality) => [
                        'pais_id' => $paisId,
                        'nombre' => $municipality['name'],
                        'codigo' => $municipality['code'],
                        'nivel' => 2,
                        'division_padre_id' => $stateIds[$municipality['state_code']],
                        'tipo' => 'municipio',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $chunk),
                );
            }

            $stateCodesById = $stateIds->flip();
            $municipalityIds = DB::table('divisiones_administrativas')
                ->where('pais_id', $paisId)
                ->where('nivel', 2)
                ->get(['id', 'codigo', 'division_padre_id'])
                ->mapWithKeys(fn (object $division) => [
                    $stateCodesById[$division->division_padre_id].'|'.$division->codigo => $division->id,
                ]);
            Log::channel('stderr')->info('SEPOMEX: municipios restaurados.');

            $seenLocalities = [];
            $localityChunk = [];
            $localitiesInserted = 0;

            foreach ($this->sepomexRows($txtFilePath) as $row) {
                $municipalityKey = $row['state_code'].'|'.$row['municipality_code'];
                $localityKey = $municipalityIds[$municipalityKey].'|'.$row['locality_code'];

                if (isset($seenLocalities[$localityKey])) {
                    continue;
                }

                $seenLocalities[$localityKey] = true;
                $localityChunk[] = [
                    'pais_id' => $paisId,
                    'nombre' => $row['locality_name'],
                    'codigo' => $row['locality_code'],
                    'nivel' => 3,
                    'division_padre_id' => $municipalityIds[$municipalityKey],
                    'tipo' => $row['locality_type'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($localityChunk) === 1000) {
                    DB::table('divisiones_administrativas')->insert($localityChunk);
                    $localitiesInserted += count($localityChunk);
                    $localityChunk = [];

                    if ($localitiesInserted % 10000 === 0) {
                        Log::channel('stderr')->info("SEPOMEX: {$localitiesInserted} asentamientos restaurados.");
                    }
                }
            }

            if ($localityChunk) {
                DB::table('divisiones_administrativas')->insert($localityChunk);
                $localitiesInserted += count($localityChunk);
            }
            Log::channel('stderr')->info("SEPOMEX: {$localitiesInserted} asentamientos restaurados.");

            unset($seenLocalities);

            $localityIds = DB::table('divisiones_administrativas')
                ->where('pais_id', $paisId)
                ->where('nivel', 3)
                ->get(['id', 'codigo', 'division_padre_id'])
                ->mapWithKeys(fn (object $division) => [
                    $division->division_padre_id.'|'.$division->codigo => $division->id,
                ]);

            $postalChunk = [];
            $processed = 0;
            $seenPostalCodes = [];

            foreach ($this->sepomexRows($txtFilePath) as $row) {
                $municipalityKey = $row['state_code'].'|'.$row['municipality_code'];
                $localityKey = $municipalityIds[$municipalityKey].'|'.$row['locality_code'];
                $postalKey = $row['postal_code'].'|'.$localityKey;

                if (isset($seenPostalCodes[$postalKey])) {
                    continue;
                }

                $seenPostalCodes[$postalKey] = true;
                $additionalData = [
                    'clave_tipo_asentamiento' => $row['locality_type_code'],
                    'zona_asentamiento' => $row['zone'],
                ];

                if ($row['city_code'] !== '') {
                    $additionalData['clave_ciudad'] = $row['city_code'];
                    $additionalData['nombre_ciudad'] = $row['city_name'];
                }

                $postalChunk[] = [
                    'codigo' => $row['postal_code'],
                    'pais_id' => $paisId,
                    'division_admin_id' => $localityIds[$localityKey],
                    'datos_adicionales' => json_encode($additionalData, JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $processed++;

                if (count($postalChunk) === 1000) {
                    DB::table('codigos_postales')->insert($postalChunk);
                    $postalChunk = [];

                    if ($processed % 10000 === 0) {
                        Log::channel('stderr')->info("SEPOMEX: {$processed} codigos postales restaurados.");
                    }
                }
            }

            if ($postalChunk) {
                DB::table('codigos_postales')->insert($postalChunk);
            }

            return $processed;
        });

        $updateDate = $lastUpdateDate?->format('Y-m-d') ?? date('Y-m-d');
        file_put_contents($this->downloadPath.'/last_update.txt', $updateDate);
        Log::channel('stderr')->info("Catálogo SEPOMEX restaurado por lotes: {$processed} registros.");

        return $processed;
    }

    private function sepomexRows(string $path): Generator
    {
        $file = new SplFileObject($path);

        foreach ($file as $index => $line) {
            if ($index < 2 || ! is_string($line) || trim($line) === '') {
                continue;
            }

            $utf8Line = iconv('ISO-8859-1', 'UTF-8//IGNORE', $line);
            $fields = explode('|', $utf8Line);

            if (count($fields) < 15) {
                continue;
            }

            yield [
                'postal_code' => trim($fields[0]),
                'locality_name' => trim($fields[1]),
                'locality_type' => trim($fields[2]),
                'municipality_name' => trim($fields[3]),
                'state_name' => trim($fields[4]),
                'city_name' => trim($fields[5]),
                'state_code' => trim($fields[7]),
                'locality_type_code' => trim($fields[10]),
                'municipality_code' => trim($fields[11]),
                'locality_code' => trim($fields[12]),
                'zone' => trim($fields[13]),
                'city_code' => trim($fields[14]),
            ];
        }
    }
}
