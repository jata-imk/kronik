<?php

use App\Models\CommunicationChannel;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Services\Communications\CommunicationWebhookService;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => $this->withoutVite());

test('Meta verifica el callback solo con el token del equipo', function () {
    $admin = actingAsSuperAdmin();
    CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'whatsapp',
        'provider' => 'meta_cloud', 'sender' => '123456789', 'eligibility' => 'approved', 'enabled' => true,
        'secret' => json_encode(['verify_token' => 'verify-qa', 'app_secret' => 'signed-secret'])]);
    $url = route('webhooks.comunicaciones.whatsapp.verify', $admin->current_team_id);
    $this->get($url.'?hub.mode=subscribe&hub.verify_token=incorrecto&hub.challenge=challenge-qa')->assertForbidden();
    $this->get($url.'?hub.mode=subscribe&hub.verify_token=verify-qa&hub.challenge=challenge-qa')
        ->assertOk()->assertSeeText('challenge-qa');
});

test('Meta exige firma y número del equipo, deduplica y no retrocede de leído a entregado', function () {
    [$actor, $credit, $data] = creditoPagableQa($this);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credit), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credit), $data)->assertSessionHasNoErrors();
    $event = CommunicationEvent::where('event_type', 'payment_received')->sole();
    CommunicationChannel::create(['team_id' => $credit->team_id, 'channel' => 'whatsapp', 'provider' => 'meta_cloud',
        'sender' => '123456789', 'eligibility' => 'approved', 'enabled' => true,
        'secret' => json_encode(['token' => 'test-token', 'api_version' => 'v27.0', 'phone_number_id' => '123456789',
            'app_secret' => 'signed-secret', 'verify_token' => 'verify-secret'])]);
    $delivery = CommunicationDelivery::create(['team_id' => $credit->team_id, 'communication_event_id' => $event->id,
        'cliente_id' => $credit->cliente_id, 'credito_id' => $credit->id, 'channel' => 'whatsapp',
        'recipient_hash' => str_repeat('a', 64), 'template_version' => 1, 'status' => 'accepted',
        'provider_message_id' => 'wamid.123']);
    $url = route('webhooks.comunicaciones.whatsapp', $credit->team_id);
    $body = fn ($status, $phone = '123456789') => json_encode(['entry' => [['changes' => [['value' => [
        'metadata' => ['phone_number_id' => $phone], 'statuses' => [['id' => 'wamid.123', 'status' => $status, 'timestamp' => '100']]],
    ]]]]], JSON_THROW_ON_ERROR);
    $send = function ($raw, $valid = true) use ($url) {
        $signature = 'sha256='.hash_hmac('sha256', $raw, $valid ? 'signed-secret' : 'wrong');

        return $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature], $raw);
    };
    $send($body('read'), false)->assertForbidden();
    expect($delivery->fresh()->status)->toBe('accepted');
    $send($body('read', 'other'))->assertOk();
    expect($delivery->fresh()->status)->toBe('accepted');
    $send($body('read'))->assertOk();
    $send($body('read'))->assertOk();
    $send($body('delivered'))->assertOk();
    expect($delivery->fresh()->status)->toBe('read')->and($delivery->fresh()->delivered_at)->not->toBeNull()
        ->and(DB::table('communication_webhook_events')->count())->toBe(2);
    expect(\App\Models\Activity::where('event', 'communication.delivery_status_updated')->count())->toBe(1);
});

test('Infobip solo acepta reporte firmado y entrega confirmada al dispositivo', function () {
    [$actor, $credit, $data] = creditoPagableQa($this);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credit), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credit), $data)->assertSessionHasNoErrors();
    $event = CommunicationEvent::where('event_type', 'payment_received')->sole();
    CommunicationChannel::create(['team_id' => $credit->team_id, 'channel' => 'sms', 'provider' => 'infobip',
        'sender' => 'Kronik', 'eligibility' => 'approved', 'enabled' => true,
        'secret' => json_encode(['api_key' => 'test', 'base_url' => 'tenant.api.infobip.com', 'webhook_secret' => 'sms-secret'])]);
    $delivery = CommunicationDelivery::create(['team_id' => $credit->team_id, 'communication_event_id' => $event->id,
        'cliente_id' => $credit->cliente_id, 'credito_id' => $credit->id, 'channel' => 'sms',
        'recipient_hash' => str_repeat('a', 64), 'template_version' => 1, 'status' => 'accepted',
        'provider_message_id' => 'sms-123']);
    $raw = json_encode(['results' => [['messageId' => 'sms-123', 'doneAt' => '2026-11-20T10:00:00',
        'status' => ['name' => 'DELIVERED_TO_HANDSET']]]], JSON_THROW_ON_ERROR);
    $url = route('webhooks.comunicaciones.sms', $credit->team_id);
    $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/json'], $raw)->assertForbidden();
    $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE' => 'sha256='.hash_hmac('sha256', $raw, 'sms-secret')], $raw)->assertOk();
    expect($delivery->fresh()->status)->toBe('delivered')->and($delivery->fresh()->delivered_at)->not->toBeNull();
});

test('un webhook adelantado se asocia después con el envío sin perder el estado', function () {
    [$actor, $credit, $data] = creditoPagableQa($this);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credit), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credit), $data)->assertSessionHasNoErrors();
    $event = CommunicationEvent::where('event_type', 'payment_received')->sole();
    $service = app(CommunicationWebhookService::class);
    $service->apply($credit->team_id, 'meta_cloud', 'wamid.early', 'read', '100');
    expect(DB::table('communication_webhook_events')->whereNull('communication_delivery_id')->count())->toBe(1);
    $delivery = CommunicationDelivery::create(['team_id' => $credit->team_id, 'communication_event_id' => $event->id,
        'cliente_id' => $credit->cliente_id, 'credito_id' => $credit->id, 'channel' => 'whatsapp',
        'recipient_hash' => str_repeat('a', 64), 'template_version' => 1, 'status' => 'accepted',
        'provider_message_id' => 'wamid.early']);
    $service->reconcile($delivery);
    expect($delivery->fresh()->status)->toBe('read')->and($delivery->fresh()->delivered_at)->not->toBeNull()
        ->and(DB::table('communication_webhook_events')->where('communication_delivery_id', $delivery->id)->count())->toBe(1);
});

test('un estado de Meta no altera una entrega SMS con el mismo identificador externo', function () {
    [$actor, $credit, $data] = creditoPagableQa($this);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credit), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credit), $data)->assertSessionHasNoErrors();
    $event = CommunicationEvent::where('event_type', 'payment_received')->sole();
    $sms = CommunicationDelivery::create(['team_id' => $credit->team_id, 'communication_event_id' => $event->id,
        'cliente_id' => $credit->cliente_id, 'credito_id' => $credit->id, 'channel' => 'sms',
        'recipient_hash' => str_repeat('a', 64), 'template_version' => 1, 'status' => 'accepted',
        'provider_message_id' => 'same-id']);
    $service = app(CommunicationWebhookService::class);
    $service->apply($credit->team_id, 'meta_cloud', 'same-id', 'read', '100');
    expect($sms->fresh()->status)->toBe('accepted')
        ->and(DB::table('communication_webhook_events')->whereNull('communication_delivery_id')->count())->toBe(1);
    $whatsapp = CommunicationDelivery::create(['team_id' => $credit->team_id, 'communication_event_id' => $event->id,
        'cliente_id' => $credit->cliente_id, 'credito_id' => $credit->id, 'channel' => 'whatsapp',
        'recipient_hash' => str_repeat('b', 64), 'template_version' => 1, 'status' => 'accepted',
        'provider_message_id' => 'same-id']);
    $service->reconcile($whatsapp);
    expect($whatsapp->fresh()->status)->toBe('read')->and($sms->fresh()->status)->toBe('accepted');
});
