<?php

namespace App\Services\SICs\CirculoDeCredito\FicoScorev2;

use App\Models\Cliente;
use App\Services\ActivityLogService;
use App\Services\SICs\ConsultaSicNoDisponible;

/** @deprecated Replace with a validated adapter under ADR 0011. */
class FicoScorev2Repository
{
    public function __construct(
        FicoScorev2Service $service,
        ActivityLogService $activityLog
    ) {}

    public function consultaScore(Cliente $cliente, $requestData = null): never
    {
        // A disabled call is not a provider query and must not create false history.
        ConsultaSicNoDisponible::rechazar();
    }
}
