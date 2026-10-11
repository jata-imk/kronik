<?php

namespace Tests\Support;

use App\Services\SICs\SicGateway;
use App\Services\SICs\SicResultado;

final class SicFixtureGateway implements SicGateway
{
    public function consultar(string $servicio, array $peticion): array
    {
        if (! app()->environment('e2e') || ! filter_var(env('E2E_SIC_FIXTURES'), FILTER_VALIDATE_BOOL)) {
            throw new \RuntimeException('Fixture SIC exclusivo de E2E.');
        }
        $body = $servicio === 'rc_fico_score' ? json_decode(file_get_contents(base_path('tests/Fixtures/Sic/reporte.json')), true, flags: JSON_THROW_ON_ERROR)
            : ['demo' => true, 'folioConsulta' => 'E2E-SCORE-001', 'score' => ['nombreScore' => $servicio === 'fintech' ? 'Fintech' : 'FICO', 'valor' => 680, 'razones' => ['A1', 'B2']]];

        return ['http_status' => 200, 'respuesta' => $body, 'resultado' => (new SicResultado)->normalizar($body)];
    }
}
