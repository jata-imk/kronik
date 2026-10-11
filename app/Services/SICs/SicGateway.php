<?php

namespace App\Services\SICs;

interface SicGateway
{
    /** @return array{http_status: int, respuesta: array, resultado: array} */
    public function consultar(string $servicio, array $peticion): array;
}
