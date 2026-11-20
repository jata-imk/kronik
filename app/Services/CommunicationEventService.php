<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\CommunicationEvent;
use App\Models\Credito;
use App\Models\CreditoPago;
use App\Services\Credito\ProyectorLecturaCredito;
use Illuminate\Support\Facades\DB;

/** Registra eventos candidatos; no selecciona canal ni despacha mensajes. */
final class CommunicationEventService
{
    public function __construct(private FechaEmpresa $fecha, private ActivityLogService $activity) {}

    public function paymentRecorded(Credito $credito, CreditoPago $pago): CommunicationEvent
    {
        $event = CommunicationEvent::firstOrCreate(
            ['event_key' => 'payment:'.$pago->id],
            ['event_type' => 'payment_received', 'team_id' => $credito->team_id, 'credito_id' => $credito->id,
                'cliente_id' => $credito->cliente_id, 'sucursal_id' => $credito->sucursal_id,
                'source_pago_id' => $pago->id],
        );
        if ($event->wasRecentlyCreated) {
            $this->audit(ActivityEvent::CommunicationEventPrepared, $event, $credito);
        }

        return $event;
    }

    public function paymentReversed(Credito $credito, CreditoPago $pago): void
    {
        $event = CommunicationEvent::where('event_key', 'payment:'.$pago->id)->whereNull('cancelled_at')->first();
        if ($event) {
            $this->cancel($event, $credito, 'payment_reversed');
        }
        // Un reverso puede cambiar el calendario; las candidatas se regeneran en la siguiente pasada.
        $this->reconcileCreditReminders($credito);
    }

    public function reconcileCreditReminders(Credito $credito): int
    {
        $today = $this->fecha->hoy()->toDateString();
        $due = $this->fecha->hoy()->addDays(3)->toDateString();
        $eligible = $this->isEligible($credito, $today, $due);
        $key = 'due:'.$credito->id.':'.$due;
        foreach (CommunicationEvent::where('credito_id', $credito->id)->where('event_type', 'upcoming_due')
            ->whereNull('cancelled_at')->get() as $pending) {
            if ($pending->due_date->toDateString() < $today
                || $this->isEligible($credito, $today, $pending->due_date->toDateString()) === false) {
                $this->cancel($pending, $credito, 'due_not_current');
            }
        }
        $existing = CommunicationEvent::where('event_key', $key)->first();

        if ($eligible !== true) {
            return 0;
        }
        if ($existing) {
            return 0;
        }
        $event = CommunicationEvent::firstOrCreate(['event_key' => $key], [
            'event_type' => 'upcoming_due', 'team_id' => $credito->team_id, 'credito_id' => $credito->id,
            'cliente_id' => $credito->cliente_id, 'sucursal_id' => $credito->sucursal_id,
            'due_date' => $due,
        ]);
        if ($event->wasRecentlyCreated) {
            $this->audit(ActivityEvent::CommunicationEventPrepared, $event, $credito);

            return 1;
        }

        return 0;
    }

    public function reconcileDueReminders(): int
    {
        $prepared = 0;
        Credito::query()->select('id')->chunkById(100, function ($credits) use (&$prepared) {
            foreach ($credits as $item) {
                DB::transaction(function () use ($item, &$prepared) {
                    $credit = Credito::lockForUpdate()->findOrFail($item->id);
                    $prepared += $this->reconcileCreditReminders($credit);
                });
            }
        });

        return $prepared;
    }

    private function isEligible(Credito $credito, string $today, string $due): ?bool
    {
        if ($credito->estado !== 'activo') {
            return false;
        }
        $reading = DB::table('credito_lecturas')->where('credito_id', $credito->id)
            ->where('corte', $today)->where('proyector_version', ProyectorLecturaCredito::VERSION)
            ->first();

        if ($reading === null || $reading->estado === 'no_disponible') {
            return null;
        }
        if ($reading->estado === 'liquidado' || $reading->dias_atraso === null || (int) $reading->dias_atraso !== 0) {
            return false;
        }

        return DB::table('credito_lectura_obligaciones')
            ->where('lectura_id', $reading->id)->where('fecha', $due)
            ->where('naturaleza', 'programada')->where('total', '>', 0)->exists();
    }

    private function cancel(CommunicationEvent $event, Credito $credito, string $reason): void
    {
        $event->update(['cancelled_at' => now(), 'cancellation_reason' => $reason]);
        $this->audit(ActivityEvent::CommunicationEventCancelled, $event, $credito);
    }

    private function audit(ActivityEvent $activity, CommunicationEvent $event, Credito $credito): void
    {
        $this->activity->log($activity, $activity->label(), $event,
            ['related' => ['type' => 'credito', 'id' => $credito->id], 'state' => $event->event_type],
            teamId: $credito->team_id, sucursalId: $credito->sucursal_id, inheritActorContext: false);
    }
}
