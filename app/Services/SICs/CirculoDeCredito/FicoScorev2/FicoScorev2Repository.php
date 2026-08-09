<?php

namespace App\Services\SICs\CirculoDeCredito\FicoScorev2;

use App\Enums\ActivityEvent;
use App\Models\Cliente;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class FicoScorev2Repository
{
    protected $service;

    public function __construct(
        FicoScorev2Service $service,
        private readonly ActivityLogService $activityLog
    ) {
        $this->service = $service;
    }

    /**
     * Realiza la consulta de FICO Score y almacena el resultado.
     *
     * @param  object  $requestData  Objeto Peticion para la consulta.
     * @return object Respuesta de la API.
     */
    public function consultaScore(Cliente $cliente, $requestData = null)
    {
        try {
            $result = $this->service->getReporte($requestData);

            $sicQuery = SicQuery::create([
                'cliente_id' => $cliente->id,
                'sic_id' => Sic::where('clave', 'circulo-credito')->first()->id,
                'sic_api_id' => SicApi::where('clave', 'fico_score_v2')->first()->id,
                'fecha_consulta' => now(),
                'status' => 'success',
                'mensaje_error' => null,
                'response_data' => json_decode($result->__toString()),
            ]);

            $this->activityLog->log(
                event: ActivityEvent::ClientSicFicoScoreV2Queried,
                description: 'Realizó una consulta de FICO Score',
                subject: $cliente,
                metadata: [
                    'related' => ['type' => 'sic_query', 'id' => $sicQuery->id],
                    'provider' => 'circulo-credito',
                    'product' => 'fico_score_v2',
                    'result' => 'success',
                ],
                causer: Auth::user(),
            );

            return $result;
        } catch (\Exception $e) {
            SicQuery::create([
                'cliente_id' => $cliente->id,
                'sic_id' => Sic::where('clave', 'circulo-credito')->first()->id,
                'sic_api_id' => SicApi::where('clave', 'fico_score_v2')->first()->id,
                'fecha_consulta' => now(),
                'status' => 'error',
                'mensaje_error' => $e->getMessage(),
                'response_data' => null,
            ]);

            throw $e;
        }
    }
}
