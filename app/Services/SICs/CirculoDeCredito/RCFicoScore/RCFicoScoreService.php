<?php

namespace App\Services\SICs\CirculoDeCredito\RCFicoScore;

use \GuzzleHttp\Client;

use App\Services\SICs\CirculoDeCredito\RCFicoScore\Configuration;
use App\Services\SICs\CirculoDeCredito\RCFicoScore\ApiException;

use App\Services\SICs\CirculoDeCredito\RCFicoScore\Api\ReporteDeCreditoConFicoScore as Instance;

use App\Services\SICs\CirculoDeCredito\RCFicoScore\Model\PersonaPeticion;
use App\Services\SICs\CirculoDeCredito\RCFicoScore\Model\CatalogoEstados;
use App\Services\SICs\CirculoDeCredito\RCFicoScore\Model\DomicilioPeticion;

class RCFicoScoreService
{
    private $apiInstance;
    private $apiKey;

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
     * Ejecuta la consulta de Reporte de Crédito con FICO Score.
     * @param  object $requestData  Objeto con los datos necesarios.
     * @return object              Respuesta de la API.
     * @throws \Exception         Si ocurre un error HTTP o en la API.
     */
    public function getReporte($requestData = null)
    {
        $persona = $requestData ?? new PersonaPeticion();
        $estado = new CatalogoEstados();
        $domicilio = new DomicilioPeticion();

        if ($requestData == null) {
            $persona->setPrimerNombre("JUAN PRUEBA SIETE");
            $persona->setApellidoPaterno("PRUEBA");
            $persona->setApellidoMaterno("SIETE");
            $persona->setFechaNacimiento("1965-08-09");
            $persona->setRfc("PUSJ800107H2O");
            $persona->setNacionalidad("MX");

            $domicilio->setDireccion("INSURGENTES SUR 1001");
            $domicilio->setColoniaPoblacion("INSURGENTES SUR");
            $domicilio->setDelegacionMunicipio("CIUDAD DE MEXICO");
            $domicilio->setCiudad("CIUDAD DE MEXICO");
            $domicilio->setEstado($estado::CDMX);
            $domicilio->setCp("11230");

            $persona->setDomicilio($domicilio);
        }

        try {
            $result = $this->apiInstance->getReporte($this->apiKey, $persona);

            return $result;
        } catch (ApiException $e) {
            echo 'Exception when calling RCFicoScoreService->getReporte: ', $e->getMessage(), PHP_EOL;
        }
    }
}
