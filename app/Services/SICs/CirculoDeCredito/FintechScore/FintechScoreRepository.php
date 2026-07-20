<?php

namespace App\Services\SICs\CirculoDeCredito\FintechScore;

use App\Models\Cliente;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use App\Services\SICs\CirculoDeCredito\FintechScore\FintechScoreService;
use Illuminate\Support\Facades\Auth;

class FintechScoreRepository
{
    protected $service;

    public function __construct(FintechScoreService $service)
    {
        $this->service = $service;
    }

    /**
     * Realiza la consulta de FINTECH Score y almacena el resultado.
     * @param  object $requestData  Objeto Peticion para la consulta.
     * @return object              Respuesta de la API.
     */
    public function consultaScore(Cliente $cliente, $requestData = null)
    {
        try {
            $result = $this->service->getReporte($requestData);

            // Guardamos en la BD (por ejemplo, respuesta completa o campos relevantes)
            $sicQuery = SicQuery::create([
                'cliente_id'     => $cliente->id,
                'sic_id'         => Sic::where('clave', 'circulo-credito')->first()->id,
                'sic_api_id'     => SicApi::where('clave', 'fintech')->first()->id,
                'fecha_consulta' => now(),
                'status'         => 'success',
                'mensaje_error'  => null,
                'response_data'  => json_decode($result->__toString())
            ]);

            activity()
                ->performedOn($cliente)
                ->causedBy(Auth::user())
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                    'sic_query_id' => $sicQuery->id
                ])
                ->log('Realizó una consulta de Reporte de Crédito con Fintech Score');

            return $result;
        } catch (\Exception $e) {
            // Registrar el fallo en la base
            SicQuery::create([
                'cliente_id'     => $cliente->id,
                'sic_id'         => Sic::where('clave', 'circulo-credito')->first()->id,
                'sic_api_id'     => SicApi::where('clave', 'fintech')->first()->id,
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
