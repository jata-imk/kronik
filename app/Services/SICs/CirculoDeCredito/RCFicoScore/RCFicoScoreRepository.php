<?php

namespace App\Services\SICs\CirculoDeCredito\RCFicoScore;

use App\Models\Cliente;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use App\Services\SICs\CirculoDeCredito\RCFicoScore\RCFicoScoreService;

class RCFicoScoreRepository
{
    protected $service;

    public function __construct(RCFicoScoreService $service)
    {
        $this->service = $service;
    }

    /**
     * Realiza la consulta de Reporte de Crédito con FICO Score y almacena el resultado.
     * @param  object $requestData  Objeto PersonaPeticion para la consulta.
     * @return object              Respuesta de la API.
     */
    public function consultaScore(Cliente $cliente, $requestData = null)
    {
        try {
            $result = $this->service->getReporte($requestData);

            // Guardamos en la BD (por ejemplo, respuesta completa o campos relevantes)
            SicQuery::create([
                'cliente_id'     => $cliente->id,
                'sic_id'         => Sic::where('clave', 'circulo-credito')->first()->id,
                'sic_api_id'     => SicApi::where('clave', 'rc_fico_score')->first()->id,
                'fecha_consulta' => now(),
                'status'         => 'success',
                'mensaje_error'  => null,
                'response_data'  => $result->__toString()
            ]);

            return $result;
        } catch (\Exception $e) {
            // Registrar el fallo en la base
            SicQuery::create([
                'cliente_id'     => $cliente->id,
                'sic_id'         => Sic::where('clave', 'circulo-credito')->first()->id,
                'sic_api_id'     => SicApi::where('clave', 'rc_fico_score')->first()->id,
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
