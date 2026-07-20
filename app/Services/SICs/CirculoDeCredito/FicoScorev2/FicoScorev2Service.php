<?php

namespace App\Services\SICs\CirculoDeCredito\FicoScorev2;

use GuzzleHttp\Client;

use App\Services\SICs\CirculoDeCredito\FicoScorev2\Configuration;
use App\Services\SICs\CirculoDeCredito\FicoScorev2\API\FicoScorev2 as Instance;
use App\Services\SICs\CirculoDeCredito\FicoScorev2\Model\Peticion;
use App\Services\SICs\CirculoDeCredito\FicoScorev2\Model\Persona;
use App\Services\SICs\CirculoDeCredito\FicoScorev2\Model\Domicilio;

use Exception;

use Illuminate\Support\Facades\Log;

class FicoScorev2Service
{
    private $apiKey;
    private $apiInstance;

    public function __construct()
    {
        $config = new Configuration();
        $config->setHost(config('services.circulo-credito.host'));
        $client = new Client([
            'verify' => false
        ]);
        $this->apiInstance = new Instance($client, $config);
        $this->apiKey = config('services.circulo-credito.api_key');
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
            $request->setFolio("123456");
        }

        $persona->setNombres("JUAN");
        $persona->setApellidoPaterno("SESENTAYDOS");
        $persona->setApellidoMaterno("PRUEBA");
        $persona->setFechaNacimiento("1965-08-09");
        $persona->setRFC("SEPJ650809JG1");

        $domicilio->setDireccion("PASADISO ENCONTRADO 58");
        $domicilio->setColoniaPoblacion("MONTEVIDEO");
        $domicilio->setCiudad("CIUDAD DE MÉXICO");
        $domicilio->setCP("07730");
        $domicilio->setDelegacionMunicipio("GUSTAVO A MADERO");
        $domicilio->setEstado("CDMX");

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
            Log::error('Error al llamar a FICO Score: ' . $e->getMessage());
            throw $e;
        }
    }
}
