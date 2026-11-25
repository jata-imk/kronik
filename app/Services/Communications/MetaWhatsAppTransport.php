<?php

namespace App\Services\Communications;

use App\Models\CommunicationChannel;
use App\Models\CommunicationRule;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MetaWhatsAppTransport implements ChannelTransport
{
    public function capabilities(): array
    {
        return ['templates' => true, 'attachments' => false, 'replies' => false, 'delivery_receipts' => true, 'read_receipts' => true];
    }

    public function send(CommunicationChannel $channel, CommunicationRule $rule, string $recipient, string $subject, string $body, array $variables = [], ?string $correlationId = null): array
    {
        if ($rule->external_template_status !== 'approved' || ! $rule->external_template_name || ! $rule->external_template_locale) {
            throw new RuntimeException('La plantilla de WhatsApp no tiene aprobación registrada.');
        }
        $settings = json_decode($channel->secret ?? '{}', true);
        if (! is_array($settings) || empty($settings['token']) || empty($settings['phone_number_id'])
            || empty($settings['waba_id']) || empty($settings['api_version'])) {
            throw new RuntimeException('La conexión de WhatsApp está incompleta.');
        }
        $version = $settings['api_version'];
        $phoneId = $settings['phone_number_id'];
        if (! preg_match('/^v\d+\.\d+$/', $version) || ! ctype_digit((string) $phoneId)
            || ! ctype_digit((string) $settings['waba_id'])) {
            throw new RuntimeException('La configuración de WhatsApp no es válida.');
        }
        $templateResponse = Http::withToken($settings['token'])->timeout(20)
            ->get("https://graph.facebook.com/{$version}/{$settings['waba_id']}/message_templates", [
                'name' => $rule->external_template_name,
            ]);
        if (! $templateResponse->successful()) {
            return ['status' => 'uncertain', 'message_id' => null];
        }
        $template = collect($templateResponse->json('data', []))->first(fn ($item) => ($item['name'] ?? null) === $rule->external_template_name
            && ($item['language'] ?? null) === $rule->external_template_locale
            && ($item['status'] ?? null) === 'APPROVED');
        if (! $template) {
            return ['status' => 'failed', 'message_id' => null];
        }
        preg_match_all('/\{(nombre|fecha_vencimiento)\}/u', $rule->body, $matches);
        $expectedBody = $rule->body;
        foreach ($matches[0] as $index => $placeholder) {
            $position = strpos($expectedBody, $placeholder);
            $expectedBody = substr_replace($expectedBody, '{{'.($index + 1).'}}', $position, strlen($placeholder));
        }
        $externalBody = collect($template['components'] ?? [])->first(fn ($component) => ($component['type'] ?? null) === 'BODY')['text'] ?? null;
        if ($externalBody !== $expectedBody) {
            return ['status' => 'failed', 'message_id' => null];
        }
        $components = $matches[1] ? [['type' => 'body', 'parameters' => array_map(
            fn ($name) => ['type' => 'text', 'text' => $variables[$name] ?? ''], $matches[1],
        )]] : [];
        $response = Http::withToken($settings['token'])->timeout(20)
            ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp', 'to' => $recipient, 'type' => 'template',
                'template' => ['name' => $rule->external_template_name, 'language' => ['code' => $rule->external_template_locale],
                    'components' => $components],
            ]);
        if (! $response->successful()) {
            // A server error may follow a successful upstream send; never retry blindly.
            return ['status' => $response->status() >= 500 ? 'uncertain' : 'failed', 'message_id' => null];
        }

        $messageId = $response->json('messages.0.id');

        return ['status' => $messageId ? 'accepted' : 'uncertain', 'message_id' => $messageId];
    }
}
