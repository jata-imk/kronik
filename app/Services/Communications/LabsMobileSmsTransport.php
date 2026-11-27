<?php

namespace App\Services\Communications;

use App\Models\CommunicationChannel;
use App\Models\CommunicationRule;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

final class LabsMobileSmsTransport implements ChannelTransport
{
    public function __construct(private LabsMobileConfiguration $configuration, private SmsText $text) {}

    public function capabilities(): array
    {
        // Tracking is deliberately unavailable until I2 establishes callback authenticity.
        return ['templates' => true, 'attachments' => false, 'replies' => false, 'delivery_receipts' => false, 'read_receipts' => false];
    }

    public function send(CommunicationChannel $channel, CommunicationRule $rule, string $recipient, string $subject, string $body, array $variables = [], ?string $correlationId = null): array
    {
        $failed = ['status' => 'failed', 'message_id' => null];
        $uncertain = ['status' => 'uncertain', 'message_id' => null];
        $measure = $this->text->measure($body);
        if (! $this->configuration->sendingEnabled() || ! $this->configuration->configured()
            || $channel->provider !== 'labsmobile' || ! $channel->enabled || $channel->eligibility !== 'approved'
            || ! preg_match('/\A\+[1-9][0-9]{7,14}\z/', $recipient)
            || ! is_string($correlationId) || ! preg_match('/\A[a-f0-9]{20}\z/', $correlationId)
            || $measure['segments'] < 1 || $measure['segments'] > 2) {
            return $failed;
        }
        try {
            $response = $this->request()->post(config('comunicaciones.sms.base_url').'/send', [
                'message' => $body, 'tpoa' => config('comunicaciones.sms.sender'),
                'recipient' => [['msisdn' => substr($recipient, 1)]], 'subid' => $correlationId,
                'long' => $measure['segments'] > 1 ? 1 : 0, 'ucs2' => $measure['unicode'] ? 1 : 0,
            ]);
            if ($response->serverError() || in_array($response->status(), [408, 429], true)) {
                return $uncertain;
            }
            if ($response->clientError()) {
                return $failed;
            }
            $code = $response->json('code');
            if (! $response->successful() || ! is_array($response->json())
                || ! (is_int($code) || (is_string($code) && ctype_digit($code)))) {
                return $uncertain;
            }
            if ((string) $code !== '0') {
                return $failed;
            }
            $id = $response->json('subid');

            return is_string($id) && hash_equals($correlationId, $id)
                ? ['status' => 'accepted', 'message_id' => $id] : $uncertain;
        } catch (Throwable) {
            return $uncertain;
        }
    }

    /** A balance check shares the same lock/quota as dispatch. */
    public function checkConnection(): bool
    {
        if (! $this->configuration->credentialsConfigured()) {
            return false;
        }

        return app(LabsMobileRequestLimiter::class)->run(function () {
            try {
                $response = $this->request()->timeout(10)->get(config('comunicaciones.sms.base_url').'/balance');
                $code = $response->json('code');
                $credits = $response->json('credits');

                return $response->successful() && in_array($code, [0, '0'], true)
                    && (is_string($credits) || is_int($credits) || is_float($credits))
                    && is_numeric($credits) && (float) $credits >= 0;
            } catch (Throwable) {
                return false;
            }
        }) === true;
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth(config('comunicaciones.sms.username'), config('comunicaciones.sms.token'))
            ->acceptJson()->asJson()->withoutRedirecting()->connectTimeout(5)->timeout(20);
    }
}
