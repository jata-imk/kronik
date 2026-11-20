<?php

use App\Models\CommunicationChannel;
use App\Models\CommunicationRule;
use App\Services\Communications\InfobipSmsTransport;
use App\Services\Communications\MetaWhatsAppTransport;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => $this->withoutVite());

test('Meta verifica plantilla aprobada y texto vigente antes del POST con parámetros', function () {
    $admin = actingAsSuperAdmin();
    $channel = CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'whatsapp',
        'provider' => 'meta_cloud', 'sender' => '123456', 'eligibility' => 'approved', 'enabled' => true,
        'secret' => json_encode(['token' => 'token-qa', 'api_version' => 'v27.0', 'waba_id' => '654321',
            'phone_number_id' => '123456', 'app_secret' => 'app-qa', 'verify_token' => 'verify-qa'])]);
    $rule = CommunicationRule::create(['team_id' => $admin->current_team_id, 'event_type' => 'payment_received',
        'channel' => 'whatsapp', 'body' => 'Hola {nombre}, pago registrado.', 'external_template_name' => 'pago_qa',
        'external_template_locale' => 'es_MX', 'external_template_status' => 'approved']);
    Http::fakeSequence()
        ->push(['data' => [['name' => 'pago_qa', 'language' => 'es_MX', 'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => 'Hola {{1}}, pago registrado.']]]]], 200)
        ->push(['messages' => [['id' => 'wamid.qa']]], 200)
        ->push(['data' => [['name' => 'pago_qa', 'language' => 'es_MX', 'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => 'Otro mensaje']]]]], 200);
    $receipt = app(MetaWhatsAppTransport::class)->send($channel, $rule, '+525512345678', '', 'Hola Ana, pago registrado.', ['nombre' => 'Ana']);
    expect($receipt)->toBe(['status' => 'accepted', 'message_id' => 'wamid.qa']);
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/messages')
        && $request['template']['components'][0]['parameters'][0]['text'] === 'Ana');

    expect(app(MetaWhatsAppTransport::class)->send($channel, $rule, '+525512345678', '', 'Hola Ana, pago registrado.', ['nombre' => 'Ana'])['status'])->toBe('failed');
    Http::assertSentCount(3);
});

test('Infobip usa el host de cuenta validado y conserva aceptación separada de entrega', function () {
    $admin = actingAsSuperAdmin();
    $channel = CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'sms',
        'provider' => 'infobip', 'sender' => 'KronikQA', 'eligibility' => 'approved', 'enabled' => true,
        'secret' => json_encode(['api_key' => 'clave-qa', 'base_url' => 'tenant.api.infobip.com', 'webhook_secret' => 'firma-qa'])]);
    $rule = CommunicationRule::create(['team_id' => $admin->current_team_id, 'event_type' => 'payment_received',
        'channel' => 'sms', 'body' => 'Pago registrado.']);
    Http::fake(['tenant.api.infobip.com/*' => Http::response(['messages' => [['messageId' => 'sms-qa']]], 200)]);
    $receipt = app(InfobipSmsTransport::class)->send($channel, $rule, '+525512345678', '', 'Pago registrado.');
    expect($receipt)->toBe(['status' => 'accepted', 'message_id' => 'sms-qa']);
    Http::assertSent(fn ($request) => $request->url() === 'https://tenant.api.infobip.com/sms/3/messages'
        && $request['messages'][0]['destinations'][0]['to'] === '525512345678'
        && $request->hasHeader('Authorization', 'App clave-qa'));
});
