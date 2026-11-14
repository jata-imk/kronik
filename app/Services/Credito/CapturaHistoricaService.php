<?php

namespace App\Services\Credito;

use App\Enums\ActivityEvent;
use App\Enums\EstadoCapturaHistorica;
use App\Models\Credito;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CapturaHistoricaService
{
    public function estado(Credito $credito): EstadoCapturaHistorica
    {
        $captura = $credito->capturaHistorica;

        return ! $captura ? EstadoCapturaHistorica::NoAplica : ($captura->completada_en ? EstadoCapturaHistorica::Completa : EstadoCapturaHistorica::Pendiente);
    }

    /** Comprobar de nuevo al programar y ejecutar un aviso; no usar una relación cacheada. */
    public function permiteAvisosFinancieros(Credito $credito): bool
    {
        return Credito::whereKey($credito->id)->conHistoriaCompleta()->exists();
    }

    public function resumen(Credito $credito): array
    {
        return ['estado' => $this->estado($credito)->value,
            'iniciada_en' => $credito->capturaHistorica?->created_at?->toIso8601String(),
            'completada_en' => $credito->capturaHistorica?->completada_en?->toIso8601String()];
    }

    public function completar(Credito $credito, User $actor): void
    {
        DB::transaction(function () use ($credito, $actor) {
            $credito = Credito::lockForUpdate()->findOrFail($credito->id);
            Gate::forUser($actor)->authorize('pay', $credito);
            if ($credito->modo !== 'qa' || config('originacion.pagos_qa_habilitados') !== true
                || ! $actor->operaEnSucursal($credito->sucursal_id)) {
                throw ValidationException::withMessages(['captura_historica' => 'Habilita pagos QA y selecciona la sucursal activa responsable para completar la captura.']);
            }
            $captura = $credito->capturaHistorica()->lockForUpdate()->first();
            if (! $captura) {
                throw ValidationException::withMessages(['captura_historica' => 'Este crédito no tiene una captura histórica pendiente.']);
            }
            if ($captura->completada_en) {
                return;
            }
            $situacion = app(PagoService::class)->consultar($credito);
            if ($situacion['error']) {
                throw ValidationException::withMessages(['captura_historica' => $situacion['error']]);
            }
            $captura->update(['completada_por' => $actor->id, 'completada_team_id' => $actor->current_team_id, 'completada_en' => now()]);
            app(ProyeccionCreditoService::class)->sincronizar($credito);
            app(ActivityLogService::class)->log(ActivityEvent::CreditHistoricalCaptureCompleted, 'Captura histórica QA completada', $credito,
                ['related' => ['type' => 'credito_captura_historica', 'id' => $captura->id], 'state' => 'completa'], $actor,
                teamId: $actor->current_team_id, sucursalId: $credito->sucursal_id, inheritActorContext: false);
        }, attempts: 5);
    }
}
