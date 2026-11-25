<?php

namespace App\Services\Communications;

use App\Enums\ActivityEvent;
use App\Jobs\SendCommunicationDelivery;
use App\Models\Cliente;
use App\Models\CommunicationChannel;
use App\Models\CommunicationConsent;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\CommunicationRule;
use App\Models\Credito;
use App\Models\CreditoPago;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Credito\ProyectorLecturaCredito;
use App\Services\FechaEmpresa;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CommunicationOrchestrator
{
    public function __construct(private ActivityLogService $activity, private FechaEmpresa $fecha) {}

    public function contact(Cliente $client, string $channel): ?string
    {
        return match ($channel) {
            'email' => $client->email ? mb_strtolower(trim($client->email)) : null,
            'sms', 'whatsapp' => $client->telefono && preg_match('/^\d{8,15}$/', $digits = preg_replace('/\D/', '', ($client->telefono_codigo_pais ?: '52').$client->telefono))
                ? '+'.$digits : null,
            default => null,
        };
    }

    public function contactHash(string $contact): string
    {
        return hash_hmac('sha256', $contact, config('app.key'));
    }

    public function grantConsent(Cliente $client, int $teamId, array $data, User $actor): CommunicationConsent
    {
        $contact = $this->contact($client, $data['channel']);
        if (! $contact) {
            throw new RuntimeException('El cliente no tiene contacto para este canal.');
        }

        return DB::transaction(function () use ($client, $teamId, $data, $actor, $contact) {
            CommunicationConsent::where('team_id', $teamId)->where('cliente_id', $client->id)
                ->where('channel', $data['channel'])->where('purpose', $data['purpose'])
                ->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $consent = CommunicationConsent::create([
                'team_id' => $teamId, 'cliente_id' => $client->id, 'channel' => $data['channel'],
                'purpose' => $data['purpose'], 'contact_hash' => $this->contactHash($contact),
                'source' => $data['source'], 'evidence_reference' => $data['evidence_reference'],
                'recorded_by' => $actor->id, 'granted_at' => now(),
            ]);
            $this->audit(ActivityEvent::CommunicationConsentGranted, $consent, $teamId, $client->sucursal_id, $actor);

            return $consent;
        });
    }

    public function revokeConsent(CommunicationConsent $consent, User $actor): void
    {
        DB::transaction(function () use ($consent, $actor) {
            $consent = CommunicationConsent::lockForUpdate()->findOrFail($consent->id);
            if ($consent->revoked_at) {
                return;
            }
            $consent->update(['revoked_at' => now()]);
            $eventType = $consent->purpose === 'operational' ? 'payment_received' : 'upcoming_due';
            $pending = CommunicationDelivery::where('team_id', $consent->team_id)->where('cliente_id', $consent->cliente_id)
                ->where('channel', $consent->channel)->whereHas('event', fn ($query) => $query->where('event_type', $eventType))
                ->whereIn('status', ['prepared', 'queued'])->lockForUpdate()->get();
            foreach ($pending as $delivery) {
                $delivery->update(['status' => 'cancelled', 'reason' => 'consent_revoked']);
                $event = CommunicationEvent::find($delivery->communication_event_id);
                $this->audit(ActivityEvent::CommunicationDeliveryCancelled, $delivery, $delivery->team_id, $event?->sucursal_id);
            }
            $this->audit(ActivityEvent::CommunicationConsentRevoked, $consent, $consent->team_id,
                Cliente::find($consent->cliente_id)?->sucursal_id, $actor);
        });
    }

    public function prepare(): int
    {
        $count = 0;
        CommunicationEvent::whereNotNull('team_id')->whereNull('cancelled_at')->select('id')->chunkById(100, function ($events) use (&$count) {
            foreach ($events as $item) {
                $event = CommunicationEvent::find($item->id);
                if (! $event || ! $this->eventCurrent($event)) {
                    continue;
                }
                $client = Cliente::find($event->cliente_id);
                foreach (CommunicationRule::where('team_id', $event->team_id)->where('event_type', $event->event_type)
                    ->where('enabled', true)->get() as $rule) {
                    $channel = CommunicationChannel::where('team_id', $event->team_id)->where('channel', $rule->channel)
                        ->where('enabled', true)->where('eligibility', 'approved')->first();
                    $contact = $client ? $this->contact($client, $rule->channel) : null;
                    if (! $channel || ! $contact || ! $this->hasConsent($event, $rule->channel, $contact)
                        || ($rule->channel === 'sms' && $channel->provider !== 'labsmobile')
                        || ! $this->ruleMatchesEvent($event, $rule)) {
                        continue;
                    }
                    if (DB::transaction(function () use ($event, $rule, $contact, $channel) {
                        $delivery = CommunicationDelivery::firstOrCreate(
                            ['communication_event_id' => $event->id, 'channel' => $rule->channel],
                            ['team_id' => $event->team_id, 'cliente_id' => $event->cliente_id, 'credito_id' => $event->credito_id,
                                'recipient_hash' => $this->contactHash($contact), 'template_version' => $rule->template_version, 'status' => 'prepared',
                                'provider' => $channel->provider,
                                'provider_correlation_id' => $rule->channel === 'sms' ? bin2hex(random_bytes(10)) : null],
                        );
                        if ($delivery->wasRecentlyCreated) {
                            $this->audit(ActivityEvent::CommunicationDeliveryPrepared, $delivery, $event->team_id, $event->sucursal_id);

                            return true;
                        }

                        return false;
                    })) {
                        $count++;
                    }
                }
            }
        });

        return $count;
    }

    public function process(): int
    {
        $processed = 0;
        if (config('comunicaciones.send_enabled') !== true) {
            return 0;
        }
        CommunicationDelivery::where('status', 'prepared')->select('id')->chunkById(100, function ($items) use (&$processed) {
            foreach ($items as $item) {
                if (DB::transaction(function () use ($item) {
                    $delivery = CommunicationDelivery::lockForUpdate()->find($item->id);
                    if (! $delivery || $delivery->status !== 'prepared') {
                        return false;
                    }
                    if ($delivery->channel === 'sms' && ! app(LabsMobileConfiguration::class)->sendingEnabled()) {
                        return false;
                    }
                    $event = CommunicationEvent::find($delivery->communication_event_id);
                    $rule = CommunicationRule::where('team_id', $delivery->team_id)
                        ->where('event_type', $event?->event_type)->where('channel', $delivery->channel)
                        ->where('enabled', true)->first();
                    if ($event && $rule && $this->eventCurrent($event) && ! $this->ruleMatchesEvent($event, $rule)) {
                        return false;
                    }
                    $delivery->update(['status' => 'queued', 'queued_at' => now()]);
                    $this->audit(ActivityEvent::CommunicationDeliveryQueued, $delivery, $delivery->team_id, $event?->sucursal_id);
                    SendCommunicationDelivery::dispatch($delivery->id)->afterCommit();

                    return true;
                })) {
                    $processed++;
                }
            }
        });

        return $processed;
    }

    public function send(int $id): bool
    {
        $sms = CommunicationDelivery::whereKey($id)->where('channel', 'sms')->exists();
        if (! $sms) {
            return $this->sendDelivery($id);
        }
        if (! app(LabsMobileConfiguration::class)->sendingEnabled() || ! app(LabsMobileConfiguration::class)->configured()) {
            return false;
        }
        $result = app(LabsMobileRequestLimiter::class)->run(fn () => $this->sendDelivery($id));
        if ($result === null) {
            DB::transaction(function () use ($id) {
                $delivery = CommunicationDelivery::lockForUpdate()->find($id);
                if ($delivery?->status === 'queued') {
                    $delivery->update(['status' => 'prepared', 'reason' => 'sms_rate_limited', 'queued_at' => null]);
                    $this->audit(ActivityEvent::CommunicationDeliveryRequeued, $delivery, $delivery->team_id, $delivery->event?->sucursal_id);
                }
            });
        }

        return $result === true;
    }

    private function sendDelivery(int $id): bool
    {
        $payload = DB::transaction(function () use ($id) {
            $delivery = CommunicationDelivery::lockForUpdate()->find($id);
            if (! $delivery || $delivery->status !== 'queued') {
                return null;
            }
            if (config('comunicaciones.send_enabled') !== true) {
                return null;
            }
            if ($delivery->channel === 'sms' && ! app(LabsMobileConfiguration::class)->sendingEnabled()) {
                return null;
            }
            $event = CommunicationEvent::find($delivery->communication_event_id);
            $rule = CommunicationRule::where('team_id', $delivery->team_id)->where('event_type', $event?->event_type)
                ->where('channel', $delivery->channel)->where('enabled', true)->first();
            $channel = CommunicationChannel::where('team_id', $delivery->team_id)->where('channel', $delivery->channel)
                ->where('enabled', true)->where('eligibility', 'approved')->first();
            $client = Cliente::find($delivery->cliente_id);
            $contact = $client ? $this->contact($client, $delivery->channel) : null;
            $invalid = ! $event || $event->team_id !== $delivery->team_id || ! $this->eventCurrent($event)
                || ! $rule || ! $channel || ! $contact || ! hash_equals($delivery->recipient_hash, $this->contactHash($contact))
                || ! $this->hasConsent($event, $delivery->channel, $contact)
                || ($rule && $rule->template_version !== $delivery->template_version);
            if ($delivery->channel === 'sms') {
                $invalid = $invalid || $delivery->provider !== 'labsmobile' || $channel?->provider !== 'labsmobile'
                    || ! preg_match('/\A[a-f0-9]{20}\z/', $delivery->provider_correlation_id ?? '');
            }
            if ($invalid) {
                $delivery->update(['status' => 'cancelled', 'reason' => 'eligibility_changed']);
                $this->audit(ActivityEvent::CommunicationDeliveryCancelled, $delivery, $delivery->team_id, $event?->sucursal_id);

                return null;
            }
            if (! $this->ruleMatchesEvent($event, $rule)) {
                $delivery->update(['status' => 'prepared', 'reason' => 'timing_changed']);
                $this->audit(ActivityEvent::CommunicationDeliveryRequeued, $delivery, $delivery->team_id, $event->sucursal_id);

                return null;
            }
            $local = now()->timezone($rule->timezone);
            $time = $local->format('H:i:s');
            if ($time < $rule->window_start || $time >= $rule->window_end) {
                return null;
            }
            $dayStart = $local->copy()->startOfDay()->utc();
            $dayEnd = $local->copy()->endOfDay()->utc();
            $dailyCount = CommunicationDelivery::where('team_id', $delivery->team_id)->where('cliente_id', $delivery->cliente_id)
                ->whereIn('status', ['sending', 'accepted', 'delivered', 'read', 'uncertain'])
                ->whereBetween('attempted_at', [$dayStart, $dayEnd])->count();
            if ($dailyCount >= $rule->daily_limit) {
                return null;
            }
            if ($delivery->channel === 'whatsapp' && $rule->external_template_status !== 'approved') {
                $delivery->update(['status' => 'cancelled', 'reason' => 'provider_not_ready']);
                $this->audit(ActivityEvent::CommunicationDeliveryCancelled, $delivery, $delivery->team_id, $event->sucursal_id);

                return null;
            }
            $message = $this->render($rule, $event, $client);
            if ($delivery->channel === 'sms') {
                $measure = app(SmsText::class)->measure($message['body']);
                if ($measure['segments'] < 1 || $measure['segments'] > 2 || ! preg_match('/\A\+[1-9][0-9]{7,14}\z/', $contact)) {
                    $delivery->update(['status' => 'cancelled', 'reason' => 'sms_content_invalid']);
                    $this->audit(ActivityEvent::CommunicationDeliveryCancelled, $delivery, $delivery->team_id, $event->sucursal_id);

                    return null;
                }
                if (! app(LabsMobileConfiguration::class)->configured()) {
                    return null;
                }
            }
            $delivery->update(['status' => 'sending', 'attempted_at' => now()]);
            $this->audit(ActivityEvent::CommunicationDeliverySending, $delivery, $delivery->team_id, $event->sucursal_id);

            return [$delivery->id, $channel, $rule, $contact, $message['subject'], $message['body'], $message['variables'], $event->sucursal_id, $delivery->provider_correlation_id];
        });
        if (! $payload) {
            return false;
        }
        [$id, $channel, $rule, $contact, $subject, $body, $variables, $sucursalId, $correlationId] = $payload;
        try {
            $transport = match ($channel->channel) {
                'email' => app(EmailTransport::class),
                'sms' => app(LabsMobileSmsTransport::class),
                'whatsapp' => app(MetaWhatsAppTransport::class),
                default => throw new RuntimeException('No existe adaptador de envío para este canal.'),
            };
            $receipt = $transport->send($channel, $rule, $contact, $subject, $body, $variables, $correlationId);
        } catch (\Throwable $exception) {
            // Transport exceptions, including timeouts, can follow an actual send.
            $receipt = ['status' => 'uncertain', 'message_id' => null];
        }
        DB::transaction(function () use ($id, $receipt, $sucursalId) {
            $delivery = CommunicationDelivery::lockForUpdate()->findOrFail($id);
            if ($delivery->status !== 'sending') {
                return;
            }
            $delivery->update(['status' => $receipt['status'], 'provider_message_id' => $receipt['message_id'],
                'accepted_at' => $receipt['status'] === 'accepted' ? now() : null]);
            $activity = match ($receipt['status']) {
                'accepted' => ActivityEvent::CommunicationDeliveryAccepted,
                'failed' => ActivityEvent::CommunicationDeliveryFailed,
                default => ActivityEvent::CommunicationDeliveryUncertain,
            };
            $this->audit($activity, $delivery, $delivery->team_id, $sucursalId);
        });
        if ($receipt['message_id'] && $channel->channel === 'whatsapp') {
            app(CommunicationWebhookService::class)->reconcile(CommunicationDelivery::findOrFail($id));
        }

        return true;
    }

    public function markInterruptedUncertain(): int
    {
        $count = 0;
        CommunicationDelivery::where('status', 'sending')->where('attempted_at', '<', now()->subMinutes(config('comunicaciones.sending_uncertain_minutes')))
            ->select('id')->chunkById(100, function ($items) use (&$count) {
                foreach ($items as $item) {
                    DB::transaction(function () use ($item, &$count) {
                        $delivery = CommunicationDelivery::lockForUpdate()->find($item->id);
                        if (! $delivery || $delivery->status !== 'sending') {
                            return;
                        }
                        $delivery->update(['status' => 'uncertain', 'reason' => 'interrupted']);
                        $event = CommunicationEvent::find($delivery->communication_event_id);
                        $this->audit(ActivityEvent::CommunicationDeliveryUncertain, $delivery, $delivery->team_id, $event?->sucursal_id);
                        $count++;
                    });
                }
            });

        return $count;
    }

    public function requeueStale(): int
    {
        $count = 0;
        CommunicationDelivery::where('status', 'queued')->where('queued_at', '<', now()->subMinutes(config('comunicaciones.queue_stale_minutes')))
            ->select('id')->chunkById(100, function ($items) use (&$count) {
                foreach ($items as $item) {
                    DB::transaction(function () use ($item, &$count) {
                        $delivery = CommunicationDelivery::lockForUpdate()->find($item->id);
                        if (! $delivery || $delivery->status !== 'queued') {
                            return;
                        }
                        $delivery->update(['status' => 'prepared', 'reason' => 'queue_delayed']);
                        $event = CommunicationEvent::find($delivery->communication_event_id);
                        $this->audit(ActivityEvent::CommunicationDeliveryRequeued, $delivery, $delivery->team_id, $event?->sucursal_id);
                        $count++;
                    });
                }
            });

        return $count;
    }

    public function eventCurrent(CommunicationEvent $event): bool
    {
        if ($event->cancelled_at || ! $event->team_id) {
            return false;
        }
        $credit = Credito::find($event->credito_id);
        if (! $credit || (int) $credit->team_id !== (int) $event->team_id || $credit->cliente_id !== $event->cliente_id) {
            return false;
        }
        if ($event->event_type === 'payment_received') {
            return $event->created_at?->greaterThanOrEqualTo(now()->subHours(config('comunicaciones.payment_event_validity_hours'))) && $event->source_pago_id
                && CreditoPago::whereKey($event->source_pago_id)->where('credito_id', $credit->id)->exists()
                && ! CreditoPago::where('reversa_de', $event->source_pago_id)->exists();
        }
        if ($event->event_type !== 'upcoming_due' || $credit->estado !== 'activo' || ! $event->due_date) {
            return false;
        }
        $today = $this->fecha->hoy()->toDateString();
        $due = $event->due_date->toDateString();
        if ($due <= $today) {
            return false;
        }
        $reading = DB::table('credito_lecturas')->where('credito_id', $credit->id)->where('corte', $today)
            ->where('proyector_version', ProyectorLecturaCredito::VERSION)->first();
        if (! $reading || $reading->estado === 'no_disponible' || $reading->estado === 'liquidado' || $reading->dias_atraso === null
            || (int) $reading->dias_atraso !== 0) {
            return false;
        }

        return DB::table('credito_lectura_obligaciones')->where('lectura_id', $reading->id)
            ->where('fecha', $due)->where('naturaleza', 'programada')->where('total', '>', 0)->exists();
    }

    private function ruleMatchesEvent(CommunicationEvent $event, CommunicationRule $rule): bool
    {
        return $event->event_type !== 'upcoming_due'
            || $event->due_date?->toDateString() === $this->fecha->hoy()->addDays($rule->lead_days)->toDateString();
    }

    private function hasConsent(CommunicationEvent $event, string $channel, string $contact): bool
    {
        $purpose = $event->event_type === 'payment_received' ? 'operational' : 'preventive';

        return CommunicationConsent::where('team_id', $event->team_id)->where('cliente_id', $event->cliente_id)
            ->where('channel', $channel)->where('purpose', $purpose)->where('contact_hash', $this->contactHash($contact))
            ->whereNull('revoked_at')->exists();
    }

    public function render(CommunicationRule $rule, CommunicationEvent $event, Cliente $client): array
    {
        $variables = ['nombre' => trim($client->primer_nombre),
            'fecha_vencimiento' => $event->due_date?->format('d/m/Y') ?? ''];
        $values = ['{nombre}' => $variables['nombre'], '{fecha_vencimiento}' => $variables['fecha_vencimiento']];

        return ['subject' => strtr($rule->subject ?? '', $values), 'body' => strtr($rule->body, $values), 'variables' => $variables];
    }

    private function audit(ActivityEvent $event, \Illuminate\Database\Eloquent\Model $subject, int $teamId, ?int $sucursalId, ?User $actor = null): void
    {
        $this->activity->log($event, $event->label(), $subject,
            ['state' => $subject->status ?? ($subject->revoked_at ? 'revoked' : 'granted')],
            causer: $actor, teamId: $teamId, sucursalId: $sucursalId, inheritActorContext: false);
    }
}
