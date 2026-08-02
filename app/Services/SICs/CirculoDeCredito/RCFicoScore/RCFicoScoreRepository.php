<?php

namespace App\Services\SICs\CirculoDeCredito\RCFicoScore;

use App\Enums\ActivityEvent;
use App\Models\Cliente;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class RCFicoScoreRepository
{
    protected $service;

    public function __construct(
        RCFicoScoreService $service,
        private readonly ActivityLogService $activityLog
    ) {
        $this->service = $service;
    }

    /**
     * Realiza la consulta de Reporte de Crédito con FICO Score y almacena el resultado.
     *
     * @param  object  $requestData  Objeto PersonaPeticion para la consulta.
     * @return object Respuesta de la API.
     */
    public function consultaScore(Cliente $cliente, $requestData = null)
    {
        try {
            $result = $this->service->getReporte($requestData);

            $sicQuery = SicQuery::create([
                'cliente_id' => $cliente->id,
                'sic_id' => Sic::where('clave', 'circulo-credito')->first()->id,
                'sic_api_id' => SicApi::where('clave', 'rc_fico_score')->first()->id,
                'fecha_consulta' => now(),
                'status' => 'success',
                'mensaje_error' => null,
                'response_data' => json_decode($result->__toString()),
            ]);

            $this->activityLog->log(
                event: ActivityEvent::ClientSicCreditReportFicoQueried,
                description: 'Realizó una consulta de Reporte de Crédito con FICO Score',
                subject: $cliente,
                metadata: [
                    'related' => ['type' => 'sic_query', 'id' => $sicQuery->id],
                    'provider' => 'circulo-credito',
                    'product' => 'rc_fico_score',
                    'result' => 'success',
                ],
                causer: Auth::user(),
            );

            return $result;
        } catch (\Exception $e) {
            SicQuery::create([
                'cliente_id' => $cliente->id,
                'sic_id' => Sic::where('clave', 'circulo-credito')->first()->id,
                'sic_api_id' => SicApi::where('clave', 'rc_fico_score')->first()->id,
                'fecha_consulta' => now(),
                'status' => 'error',
                'mensaje_error' => $e->getMessage(),
                'response_data' => null,
            ]);

            throw $e;
        }
    }
}
