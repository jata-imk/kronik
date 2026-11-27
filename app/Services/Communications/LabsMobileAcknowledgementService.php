<?php

namespace App\Services\Communications;

use App\Enums\ActivityEvent;
use App\Models\CommunicationDelivery;
use App\Services\ActivityLogService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Internal reconciliation only: I2 has no HTTP/CLI ingress or authenticity verifier.
 * Only synthetic tests call record(). A future ingress MUST authenticate first.
 */
final class LabsMobileAcknowledgementService
{
    private const STATES = ['sending' => 0, 'uncertain' => 0, 'accepted' => 1, 'failed' => 2, 'delivered' => 3];

    private const LEVELS = ['gateway' => 1, 'operator' => 2, 'error' => 3, 'handset' => 4];

    public function __construct(private ActivityLogService $activity) {}

    public function record(LabsMobileAcknowledgement $ack): bool
    {
        return DB::transaction(function () use ($ack) {
            $delivery = CommunicationDelivery::where('channel', 'sms')->where('provider', 'labsmobile')
                ->where('provider_correlation_id', $ack->correlationId)->lockForUpdate()->first();
            if (! $delivery || ! $this->eligible($delivery)
                || ! hash_equals($delivery->recipient_hash, hash_hmac('sha256', $ack->recipient, config('app.key')))
                || $ack->occurredAt->getTimestamp() < CarbonImmutable::parse($delivery->getRawOriginal('attempted_at'), config('app.timezone'))->getTimestamp()) {
                return false;
            }
            $inserted = DB::table('communication_webhook_events')->insertOrIgnore([
                'team_id' => $delivery->team_id, 'communication_delivery_id' => $delivery->id,
                'provider' => 'labsmobile', 'provider_message_id' => $ack->correlationId,
                'provider_correlation_id' => $ack->correlationId, 'event_key' => $ack->eventKey(),
                'status' => $ack->status(), 'ack_level' => $ack->level, 'provider_status' => $ack->result,
                'provider_code' => $ack->code, 'occurred_at' => $ack->occurredAt, 'received_at' => now(),
            ]);
            if ($inserted) {
                $this->reconcileLocked($delivery);
            }

            return (bool) $inserted;
        });
    }

    public function reconcile(CommunicationDelivery $delivery): void
    {
        DB::transaction(function () use ($delivery) {
            $delivery = CommunicationDelivery::lockForUpdate()->findOrFail($delivery->id);
            if ($this->eligible($delivery)) {
                $this->reconcileLocked($delivery);
            }
        });
    }

    private function eligible(CommunicationDelivery $delivery): bool
    {
        return $delivery->channel === 'sms' && $delivery->provider === 'labsmobile'
            && preg_match('/\A[a-f0-9]{20}\z/', $delivery->provider_correlation_id ?? '')
            && ($delivery->provider_message_id === null || $delivery->provider_message_id === $delivery->provider_correlation_id)
            && $delivery->attempted_at !== null && isset(self::STATES[$delivery->status])
            && $delivery->event && (int) $delivery->event->team_id === (int) $delivery->team_id;
    }

    private function reconcileLocked(CommunicationDelivery $delivery): void
    {
        $receipts = DB::table('communication_webhook_events')->where('communication_delivery_id', $delivery->id)
            ->where('team_id', $delivery->team_id)->where('provider', 'labsmobile')
            ->where('provider_correlation_id', $delivery->provider_correlation_id)->orderBy('id')->get();
        $highest = 0;
        foreach ($receipts as $item) {
            if ($item->applied_at !== null) {
                $highest = max($highest, self::LEVELS[$item->ack_level] ?? 0);
            }
        }
        foreach ($receipts as $item) {
            if ($item->applied_at !== null) {
                continue;
            }
            $rank = self::LEVELS[$item->ack_level] ?? 0;
            $stateRank = self::STATES[$item->status] ?? -1;
            $currentRank = self::STATES[$delivery->status];
            $advanced = $stateRank > $currentRank || ($stateRank === $currentRank && $rank > $highest);
            if ($advanced) {
                $update = [];
                if ($stateRank > $currentRank) {
                    $update['status'] = $item->status;
                    if ($item->status === 'delivered') {
                        $update['delivered_at'] = CarbonImmutable::parse($item->occurred_at, 'UTC')->setTimezone(config('app.timezone'));
                    }
                }
                if ($update) {
                    $delivery->update($update);
                }
                $this->activity->log(ActivityEvent::CommunicationDeliveryStatusUpdated,
                    'Acuse SMS conciliado internamente', $delivery,
                    ['state' => $delivery->status, 'provider' => 'labsmobile', 'result' => $item->ack_level],
                    teamId: $delivery->team_id, sucursalId: $delivery->event->sucursal_id, inheritActorContext: false);
            }
            DB::table('communication_webhook_events')->where('id', $item->id)->update(['applied_at' => now()]);
            $highest = max($highest, $rank);
        }
    }
}
