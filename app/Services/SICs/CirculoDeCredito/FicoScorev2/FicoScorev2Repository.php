<?php

namespace App\Services\SICs\CirculoDeCredito\FicoScorev2;

use App\Models\Cliente;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use App\Services\SICs\CirculoDeCredito\FicoScorev2\FicoScorev2Service;
use Illuminate\Support\Facades\Auth;

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
    public function consultaScore(Cliente $cliente, $requestData = null)
    {
        try {
            $result = $this->service->getReporte($requestData);

            // Guardamos en la BD (por ejemplo, respuesta completa o campos relevantes)
            $SicQuery = SicQuery::create([
                'cliente_id'     => $cliente->id,
                'sic_id'         => Sic::where('clave', 'circulo-credito')->first()->id,
                'sic_api_id'     => SicApi::where('clave', 'fico_score_v2')->first()->id,
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
                    'sic_query_id' => $SicQuery->id
                ])
                ->log('Realizó una consulta de FICO Score');

            return $result;
        } catch (\Exception $e) {
            // Registrar el fallo en la base
            SicQuery::create([
                'cliente_id'     => $cliente->id,
                'sic_id'         => Sic::where('clave', 'circulo-credito')->first()->id,
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
