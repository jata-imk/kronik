<?php

namespace App\Jobs;

use App\Services\SICs\SicOperacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ConsultarSicQa implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public int $consultaId) {}

    public function handle(SicOperacionService $service): void
    {
        $service->ejecutar($this->consultaId);
    }

    public function failed(?\Throwable $exception): void
    {
        // A killed worker may have sent the request. Never resend it on recovery.
        app(SicOperacionService::class)->marcarIncierta($this->consultaId);
    }
}
