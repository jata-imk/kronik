<?php

namespace App\Http\Controllers;

use App\Models\CommunicationChannel;
use App\Services\Communications\CommunicationWebhookService;
use Illuminate\Http\Request;

class CommunicationWebhookController extends Controller
{
    public function verifyMeta(Request $request, int $team)
    {
        $channel = CommunicationChannel::where('team_id', $team)->where('channel', 'whatsapp')->firstOrFail();
        $settings = json_decode($channel->secret ?? '{}', true);
        abort_unless(is_array($settings) && filled($settings['verify_token'] ?? null)
            && ($request->query('hub.mode') ?? $request->query('hub_mode')) === 'subscribe'
            && hash_equals($settings['verify_token'], (string) ($request->query('hub.verify_token') ?? $request->query('hub_verify_token'))), 403);

        return response((string) $request->query('hub.challenge'), 200)->header('Content-Type', 'text/plain');
    }

    public function meta(Request $request, int $team, CommunicationWebhookService $service)
    {
        $channel = CommunicationChannel::where('team_id', $team)->where('channel', 'whatsapp')->firstOrFail();
        $settings = json_decode($channel->secret ?? '{}', true);
        $signature = $request->header('X-Hub-Signature-256', '');
        abort_unless(is_array($settings) && filled($settings['app_secret'] ?? null)
            && hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $settings['app_secret']), $signature), 403);
        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                if ((string) ($value['metadata']['phone_number_id'] ?? '') !== (string) $channel->sender) {
                    continue;
                }
                foreach ($value['statuses'] ?? [] as $status) {
                    $service->apply($team, 'meta_cloud', (string) ($status['id'] ?? ''),
                        (string) ($status['status'] ?? ''), (string) ($status['timestamp'] ?? ''));
                }
            }
        }

        return response()->json(['received' => true]);
    }

    public function sms(Request $request, int $team, CommunicationWebhookService $service)
    {
        $channel = CommunicationChannel::where('team_id', $team)->where('channel', 'sms')->firstOrFail();
        $settings = json_decode($channel->secret ?? '{}', true);
        $signature = $request->header('X-Hub-Signature', '');
        $expected = is_array($settings) && filled($settings['webhook_secret'] ?? null)
            ? hash_hmac('sha256', $request->getContent(), $settings['webhook_secret']) : '';
        abort_unless($expected !== '' && (hash_equals($expected, $signature) || hash_equals('sha256='.$expected, $signature)), 403);
        foreach ($request->input('results', []) as $result) {
            $service->apply($team, 'infobip', (string) ($result['messageId'] ?? ''),
                (string) ($result['status']['name'] ?? ''), (string) ($result['doneAt'] ?? ''));
        }

        return response()->json(['received' => true]);
    }
}
