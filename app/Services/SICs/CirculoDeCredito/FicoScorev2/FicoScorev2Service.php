<?php

namespace App\Services\SICs\CirculoDeCredito\FicoScorev2;

use App\Services\SICs\ConsultaSicNoDisponible;

/**
 * Legacy entry point retained for compatibility. See ADR 0011.
 * SDKs remain available for a separately validated, contracted adapter.
 */
class FicoScorev2Service
{
    public function getReporte($requestData = null): never
    {
        ConsultaSicNoDisponible::rechazar();
    }
}
