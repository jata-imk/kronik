<?php

use App\Models\CommunicationChannel;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Services\Communications\CommunicationWebhookService;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => $this->withoutVite());

test('la verificación Meta devuelve el desafío exacto con nombres originales o normalizados por PHP', function (bool $normalized) {
    $actor = \App\Models\User::factory()->withPersonalTeam()->create();
    $team = $actor->ownedTeams()->sole();
    CommunicationChannel::create(['team_id' => $team->id, 'channel' => 'whatsapp', 'provider' => 'meta_cloud',
        'sender' => 'synthetic-number', 'enabled' => false, 'eligibility' => 'testing',
        'secret' => json_encode(['verify_token' => 'synthetic-verify-token'])]);
    $query = ['hub.mode' => 'subscribe', 'hub.verify_token' => 'synthetic-verify-token', 'hub.challenge' => '00123456789'];
    if ($normalized) {
        parse_str(http_build_query($query), $query);
    }
    $url = route('webhooks.comunicaciones.whatsapp.verify', $team);
    $before = \App\Models\Activity::count();
    $this->call('GET', $url, $query)->assertOk()->assertSeeText('00123456789')->assertContent('00123456789');
    $query[$normalized ? 'hub_verify_token' : 'hub.verify_token'] = 'invalid-synthetic-token';
    $this->call('GET', $url, $query)->assertForbidden();
    expect(\App\Models\Activity::count())->toBe($before)
        ->and(DB::table('communication_webhook_events')->count())->toBe(0);
})->with(['nombres originales' => false, 'nombres normalizados por PHP' => true]);

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

test('el endpoint SMS anterior está retirado y no se publica uno LabsMobile', function () {
    $this->postJson('/api/webhooks/comunicaciones/sms/1', ['results' => []])->assertNotFound();
    expect(\Illuminate\Support\Facades\Route::has('webhooks.comunicaciones.sms'))->toBeFalse();
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
