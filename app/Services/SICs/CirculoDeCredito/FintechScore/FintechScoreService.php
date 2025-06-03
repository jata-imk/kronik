<?php

namespace App\Services\SICs\CirculoDeCredito\FintechScore;

use GuzzleHttp\Client;

use App\Services\SICs\CirculoDeCredito\FintechScore\Configuration;
use App\Services\SICs\CirculoDeCredito\FintechScore\API\FintechScore as Instance;
use App\Services\SICs\CirculoDeCredito\FintechScore\Model\Peticion;
use App\Services\SICs\CirculoDeCredito\FintechScore\Model\Persona;
use App\Services\SICs\CirculoDeCredito\FintechScore\Model\Domicilio;

use Exception;

use Illuminate\Support\Facades\Log;

class FintechScoreService
{
    private $apiKey;
    private $apiInstance;

    public function __construct()
    {
        $config = new Configuration();
        $config->setHost(env('CIRCULO_CREDITO_HOST'));
        $client = new Client([
            'verify' => false
        ]);
        $this->apiInstance = new Instance($client, $config);
        $this->apiKey = env('CIRCULO_CREDITO_API_KEY');
    }

    /**
     * Ejecuta la consulta de FICO Score.
     * @param  object $requestData  Objeto Peticion con los datos necesarios.
     * @return object              Respuesta de la API.
     * @throws \Exception         Si ocurre un error HTTP o en la API.
     */
    public function getReporte($requestData = null)
    {
        $request = $requestData ?? new Peticion();
        $persona = new Persona();
        $domicilio = new Domicilio();

        if ($requestData == null) {
            $request->setFolioOtorgante("20210308");
        }

        $persona->setApellidoPaterno("PRUEBA");
        $persona->setApellidoMaterno("HERNANDEZ");
        $persona->setPrimerNombre("SEBASTIAN");
        $persona->setFechaNacimiento("1986-12-07");
        $persona->setRFC("PUHS8612075KA");

        $domicilio->setDireccion("ORIENTE 245 NO. 373 NO. 3");
        $domicilio->setColoniaPoblacion("AGRICOLA ORIENTAL");
        $domicilio->setDelegacionMunicipio("IZTACALCO");
        $domicilio->setCiudad("CIUDAD DE MÉXICO");
        $domicilio->setEstado("CDMX");
        $domicilio->setCP("08500");
        $domicilio->setPais("MX");

        $persona->setDomicilio($domicilio);

        if ($requestData == null) {
            $request->setPersona($persona);
        }

        try {
            $result = $this->apiInstance->getReporte($this->apiKey, $request);
            return $result;
        } catch (Exception $e) {
            // Manejar el error: podríamos registrar o volver a lanzar
            // Nota: Laravel por defecto no lanza excepción HTTP en status >=400:contentReference[oaicite:1]{index=1},
            // así que verificamos manualmente o capturamos excepciones de Guzzle/SDK.
            Log::error('Error al llamar a FintechScore: ' . $e->getMessage());
            throw $e;
        }
    }
}
