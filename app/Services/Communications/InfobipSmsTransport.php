<?php

namespace App\Services\Communications;

use App\Models\CommunicationChannel;
use App\Models\CommunicationRule;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class InfobipSmsTransport implements ChannelTransport
{
    public function capabilities(): array
    {
        return ['templates' => true, 'attachments' => false, 'replies' => false, 'delivery_receipts' => true, 'read_receipts' => false];
    }

    public function send(CommunicationChannel $channel, CommunicationRule $rule, string $recipient, string $subject, string $body, array $variables = []): array
    {
        $settings = json_decode($channel->secret ?? '{}', true);
        $base = $settings['base_url'] ?? '';
        if (! is_array($settings) || empty($settings['api_key']) || ! preg_match('/\A[a-z0-9-]+\.api(?:-[a-z0-9]+)?\.infobip\.com\z/i', $base)) {
            throw new RuntimeException('La conexión SMS está incompleta.');
        }
        $response = Http::withHeaders(['Authorization' => 'App '.$settings['api_key']])->timeout(20)
            ->post('https://'.$base.'/sms/3/messages', ['messages' => [[
                'sender' => $channel->sender,
                'destinations' => [['to' => ltrim($recipient, '+')]],
                'content' => ['text' => $body],
                'webhooks' => ['delivery' => ['url' => route('webhooks.comunicaciones.sms', $channel->team_id)]],
            ]]]);
        if (! $response->successful()) {
            return ['status' => $response->status() >= 500 ? 'uncertain' : 'failed', 'message_id' => null];
        }
        $messageId = $response->json('messages.0.messageId');

        return ['status' => $messageId ? 'accepted' : 'uncertain', 'message_id' => $messageId];
    }
}
