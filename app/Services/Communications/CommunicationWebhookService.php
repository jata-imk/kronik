<?php

namespace App\Services\Communications;

use App\Enums\ActivityEvent;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

final class CommunicationWebhookService
{
    public function __construct(private ActivityLogService $activity) {}

    public function apply(int $teamId, string $provider, string $messageId, string $providerStatus, string $providerTime): void
    {
        $status = match ($provider) {
            'meta_cloud' => match ($providerStatus) {
                'sent' => 'accepted', 'delivered' => 'delivered', 'read' => 'read', 'failed' => 'failed', default => null,
            },
            'infobip' => match ($providerStatus) {
                'DELIVERED_TO_HANDSET' => 'delivered',
                'REJECTED', 'UNDELIVERABLE', 'EXPIRED' => 'failed',
                default => null,
            },
            default => null,
        };
        if (! $status || $messageId === '') {
            return;
        }
        DB::transaction(function () use ($teamId, $provider, $messageId, $providerStatus, $providerTime, $status) {
            $key = hash('sha256', implode('|', [$teamId, $provider, $messageId, $providerStatus, $providerTime]));
            if (DB::table('communication_webhook_events')->where('event_key', $key)->exists()) {
                return;
            }
            $channel = $provider === 'meta_cloud' ? 'whatsapp' : 'sms';
            $delivery = CommunicationDelivery::where('team_id', $teamId)->where('channel', $channel)
                ->where('provider_message_id', $messageId)
                ->lockForUpdate()->first();
            $inserted = DB::table('communication_webhook_events')->insertOrIgnore([
                'team_id' => $teamId, 'communication_delivery_id' => $delivery?->id,
                'provider' => $provider, 'provider_message_id' => $messageId,
                'event_key' => $key, 'status' => $status, 'received_at' => now(),
            ]);
            if ($inserted && $delivery) {
                $this->promote($delivery, $status, $provider);
            }
        });
    }

    public function reconcile(CommunicationDelivery $delivery): void
    {
        if (! $delivery->provider_message_id) {
            return;
        }
        DB::transaction(function () use ($delivery) {
            $delivery = CommunicationDelivery::lockForUpdate()->findOrFail($delivery->id);
            $provider = $delivery->channel === 'sms' ? 'infobip' : 'meta_cloud';
            $pending = DB::table('communication_webhook_events')->where('team_id', $delivery->team_id)
                ->where('provider', $provider)->where('provider_message_id', $delivery->provider_message_id)
                ->whereNull('communication_delivery_id')->orderBy('id')->get();
            foreach ($pending as $item) {
                DB::table('communication_webhook_events')->where('id', $item->id)->update(['communication_delivery_id' => $delivery->id]);
                $this->promote($delivery, $item->status, $provider);
            }
        });
    }

    private function promote(CommunicationDelivery $delivery, string $status, string $provider): void
    {
        $rank = ['sending' => 0, 'uncertain' => 0, 'accepted' => 1, 'failed' => 2, 'delivered' => 3, 'read' => 4];
        if (! isset($rank[$delivery->status]) || ! in_array($delivery->status, ['sending', 'uncertain', 'accepted', 'failed', 'delivered', 'read'], true)
            || $rank[$status] <= $rank[$delivery->status] || ($status === 'failed' && in_array($delivery->status, ['delivered', 'read'], true))) {
            return;
        }
        $update = ['status' => $status];
        if ($status === 'delivered') {
            $update['delivered_at'] = now();
        }
        if ($status === 'read') {
            $update['delivered_at'] = $delivery->delivered_at ?? now();
            $update['read_at'] = now();
        }
        $delivery->update($update);
        $event = CommunicationEvent::find($delivery->communication_event_id);
        $this->activity->log(ActivityEvent::CommunicationDeliveryStatusUpdated, 'Estado de envío confirmado por proveedor',
            $delivery, ['state' => $status, 'provider' => $provider], teamId: $delivery->team_id,
            sucursalId: $event?->sucursal_id, inheritActorContext: false);
    }
}
