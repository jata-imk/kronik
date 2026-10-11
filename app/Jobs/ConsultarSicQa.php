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
        \Illuminate\Support\Facades\DB::transaction(function () {
            $q = \App\Models\SicQuery::lockForUpdate()->find($this->consultaId);
            if ($q?->estado_operacion === 'enviando') {
                $q->update(['estado_operacion' => 'incierta']);
                \App\Models\SicEvento::create(['sic_query_id' => $q->id, 'estado' => 'incierta', 'actor_id' => $q->actor_id]);
            }
        });
    }
}
