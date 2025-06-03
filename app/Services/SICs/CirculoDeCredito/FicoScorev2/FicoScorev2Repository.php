<?php

namespace App\Services\SICs\CirculoDeCredito\FicoScorev2;

use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use App\Services\SICs\CirculoDeCredito\FicoScorev2\FicoScorev2Service;

class FicoScorev2Repository
{
    protected $service;

    public function __construct(FicoScorev2Service $service)
    {
        $this->service = $service;
    }

    /**
     * Realiza la consulta de FICO Score y almacena el resultado.
     * @param  object $requestData  Objeto Peticion para la consulta.
     * @return object              Respuesta de la API.
     */
    public function consultaScore($requestData = null)
    {
        try {
            $result = $this->service->getReporte($requestData);

            // Guardamos en la BD (por ejemplo, respuesta completa o campos relevantes)
            SicQuery::create([
                'cliente_id'     => 8,
                'sic_id'         => Sic::where('clave', 'circulo_credito')->first()->id,
                'sic_api_id'     => SicApi::where('clave', 'fico_score_v2')->first()->id,
                'fecha_consulta' => now(),
                'status'         => 'success',
                'mensaje_error'  => null,
                'response_data'  => $result->__toString()
            ]);

            return $result;
        } catch (\Exception $e) {
            // Registrar el fallo en la base
            SicQuery::create([
                'cliente_id'     => 8,
                'sic_id'         => Sic::where('clave', 'circulo_credito')->first()->id,
                'sic_api_id'     => SicApi::where('clave', 'fico_score_v2')->first()->id,
                'fecha_consulta' => now(),
                'status'         => 'error',
                'mensaje_error'  => $e->getMessage(),
                'response_data'  => null
            ]);
            // Opcional: propagar excepción o devolver null
            throw $e;
        }
    }
}
