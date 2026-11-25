<?php

use App\Models\Activity;
use App\Models\Cliente;
use App\Models\CommunicationChannel;
use App\Models\CommunicationConsent;
use App\Models\CommunicationRule;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

test('solo permisos del equipo activo permiten configurar y consultar sin exponer credenciales', function () {
    $admin = actingAsSuperAdmin();
    $this->actingAs($admin);
    $secret = json_encode(['token' => 'secreto-prueba', 'api_version' => 'v27.0', 'waba_id' => '654321', 'phone_number_id' => '123456',
        'app_secret' => 'firma-prueba', 'verify_token' => 'verificacion-prueba']);
    $this->put(route('comunicaciones.channels.update'), ['channel' => 'whatsapp', 'sender' => '123456',
        'secret' => $secret, 'eligibility' => 'testing', 'enabled' => false])->assertSessionHasNoErrors();
    $saved = CommunicationChannel::sole();
    expect(Activity::where('event', 'communication.channel_configured')->count())->toBe(1);
    expect($saved->secret)->toBe($secret);
    expect($saved->getRawOriginal('secret'))->not->toContain('secreto-prueba');
    $response = $this->get(route('comunicaciones.index'))->assertOk();
    expect($response->getContent())->not->toContain('secreto-prueba')->not->toContain('firma-prueba');

    $other = User::factory()->withPersonalTeam()->create();
    $other->forceFill(['current_sucursal_id' => $admin->current_sucursal_id])->save();
    $this->actingAs($other)->get(route('comunicaciones.index'))->assertForbidden();
    $this->put(route('comunicaciones.channels.update'), ['channel' => 'email', 'sender' => 'x@example.test',
        'eligibility' => 'approved', 'eligibility_reference' => 'QA', 'enabled' => true])->assertForbidden();
});

test('la gestión valida elegibilidad y plantillas; la prueba real queda bloqueada en transporte log', function () {
    $admin = actingAsSuperAdmin();
    $this->actingAs($admin);
    $this->put(route('comunicaciones.channels.update'), ['channel' => 'email', 'sender' => 'avisos@example.test',
        'eligibility' => 'testing', 'enabled' => true])->assertSessionHasErrors('enabled');
    $this->put(route('comunicaciones.channels.update'), ['channel' => 'email', 'sender' => 'avisos@example.test',
        'eligibility' => 'approved', 'eligibility_reference' => 'QA-EMAIL', 'enabled' => true])->assertSessionHasNoErrors();
    $data = ['event_type' => 'payment_received', 'channel' => 'email', 'enabled' => true,
        'timezone' => 'America/Mexico_City', 'window_start' => '09:00', 'window_end' => '20:00',
        'daily_limit' => 1, 'subject' => 'Pago recibido', 'body' => 'Hola {nombre}, registramos tu pago.'];
    $this->put(route('comunicaciones.rules.update'), [...$data, 'body' => 'Saldo {importe}'])->assertSessionHasErrors('body');
    $this->put(route('comunicaciones.rules.update'), [...$data, 'window_end' => '08:00'])
        ->assertSessionHasErrors(['window_end' => 'El campo fin de la ventana debe ser posterior a inicio de la ventana.']);
    $this->put(route('comunicaciones.rules.update'), $data)->assertSessionHasNoErrors();
    expect(CommunicationRule::sole()->template_version)->toBe(1);
    $this->put(route('comunicaciones.rules.update'), [...$data, 'body' => 'Gracias por tu pago, {nombre}.'])->assertSessionHasNoErrors();
    expect(CommunicationRule::sole()->template_version)->toBe(2);
    expect(Activity::where('event', 'communication.rule_configured')->count())->toBe(2);
    $dueRule = [...$data, 'event_type' => 'upcoming_due', 'lead_days' => 0];
    $this->put(route('comunicaciones.rules.update'), $dueRule)->assertSessionHasErrors('lead_days');
    $this->put(route('comunicaciones.rules.update'), [...$dueRule, 'lead_days' => 31])->assertSessionHasErrors('lead_days');
    $this->put(route('comunicaciones.rules.update'), [...$dueRule, 'lead_days' => 7])->assertSessionHasNoErrors();
    expect(CommunicationRule::where('event_type', 'upcoming_due')->sole()->lead_days)->toBe(7);
    Mail::fake();
    $this->post(route('comunicaciones.test'), ['channel' => 'email', 'recipient' => 'destinatario@example.test'])
        ->assertSessionHasErrors('recipient');
    Mail::assertNothingSent();
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.test_recipient' => 'destinatario@example.test', 'mail.default' => 'smtp']);
    $this->post(route('comunicaciones.test'), ['channel' => 'email', 'recipient' => 'destinatario@example.test'])
        ->assertSessionHasNoErrors();
    Mail::assertSentCount(1);
    expect(Activity::where('event', 'communication.test_sent')->count())->toBe(1);
});

test('consentimiento registra origen y evidencia sin mostrarlos en el historial y permite baja', function () {
    $admin = actingAsSuperAdmin();
    $client = Cliente::factory()->create(['sucursal_id' => $admin->current_sucursal_id, 'email' => 'cliente@example.test']);
    $this->actingAs($admin);
    $this->post(route('comunicaciones.consents.store', $client), ['channel' => 'email', 'purpose' => 'operational',
        'source' => 'contrato', 'evidence_reference' => 'archivo-privado-123'])->assertSessionHasNoErrors();
    $consent = CommunicationConsent::sole();
    expect(Activity::where('event', 'communication.consent_granted')->count())->toBe(1);
    expect($consent->team_id)->toBe($admin->current_team_id)
        ->and($consent->contact_hash)->not->toBe('cliente@example.test');
    $response = $this->get(route('comunicaciones.index'))->assertOk();
    expect($response->getContent())->not->toContain('archivo-privado-123')->not->toContain('cliente@example.test');
    $this->patch(route('comunicaciones.consents.revoke', [$client, $consent]))->assertSessionHasNoErrors();
    expect($consent->fresh()->revoked_at)->not->toBeNull();
    expect(Activity::where('event', 'communication.consent_revoked')->count())->toBe(1);
});

test('la ficha del cliente muestra solo autorizaciones de su equipo y enlaza su gestión', function () {
    $admin = actingAsSuperAdmin();
    $client = Cliente::factory()->create(['sucursal_id' => $admin->current_sucursal_id, 'email' => 'cliente@example.test']);
    $this->actingAs($admin);
    $this->post(route('comunicaciones.consents.store', $client), ['channel' => 'email', 'purpose' => 'operational',
        'source' => 'contrato', 'evidence_reference' => 'archivo-privado-123'])->assertSessionHasNoErrors();
    $this->get(route('clientes.show', $client))->assertInertia(fn (Assert $page) => $page
        ->has('communicationConsents', 1)->where('communicationConsents.0.channel', 'email')
        ->where('canManageCommunications', true));
    $this->get(route('comunicaciones.index', ['tab' => 'consents', 'cliente_id' => $client->id]))
        ->assertInertia(fn (Assert $page) => $page->where('activeTab', 'consents')
            ->where('filters.cliente_id', (string) $client->id)->has('consents', 1));

    $admin->forceFill(['is_super_admin' => false])->save();
    grantTeamPermissions($admin, $admin->currentTeam, ['read clientes']);
    $this->get(route('clientes.show', $client))->assertInertia(fn (Assert $page) => $page
        ->where('communicationConsents', null)->where('canManageCommunications', false));
});

test('la comprobación de conexión consulta APIs sin enviar mensajes ni confundirla con entrega', function () {
    $admin = actingAsSuperAdmin();
    $this->actingAs($admin);
    CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'sms', 'provider' => 'infobip',
        'sender' => 'Kronik', 'eligibility' => 'testing', 'enabled' => false,
        'secret' => json_encode(['api_key' => 'test', 'base_url' => 'tenant.api.infobip.com', 'webhook_secret' => 'firma'])]);
    CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'whatsapp', 'provider' => 'meta_cloud',
        'sender' => '123456', 'eligibility' => 'testing', 'enabled' => false,
        'secret' => json_encode(['token' => 'test', 'api_version' => 'v27.0', 'waba_id' => '654321',
            'phone_number_id' => '123456', 'app_secret' => 'firma', 'verify_token' => 'verificar'])]);
    Http::fake([
        'https://tenant.api.infobip.com/sms/3/logs*' => Http::response(['results' => []], 200),
        'https://graph.facebook.com/v27.0/654321/message_templates*' => Http::response(['data' => []], 200),
    ]);
    foreach (['sms', 'whatsapp'] as $channel) {
        $this->post(route('comunicaciones.connection.check'), ['channel' => $channel])->assertSessionHasNoErrors();
        expect(CommunicationChannel::where('channel', $channel)->sole()->checked_at)->not->toBeNull();
    }
    Http::assertSentCount(2);
    Http::assertNotSent(fn ($request) => $request->method() !== 'GET');
    expect(Activity::where('event', 'communication.test_sent')->count())->toBe(2);
});

test('la prueba WhatsApp usa solo el destinatario permitido, datos ficticios y deja auditoría', function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    $admin = actingAsSuperAdmin();
    $this->actingAs($admin);
    config(['comunicaciones.whatsapp_test_enabled' => true,
        'comunicaciones.whatsapp_test_recipient' => '+525512345678']);
    CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'whatsapp',
        'provider' => 'meta_cloud', 'sender' => '123456', 'eligibility' => 'testing', 'enabled' => false,
        'secret' => json_encode(['token' => 'token-qa', 'api_version' => 'v27.0', 'waba_id' => '654321',
            'phone_number_id' => '123456', 'app_secret' => 'app-qa', 'verify_token' => 'verify-qa'])]);
    CommunicationRule::create(['team_id' => $admin->current_team_id, 'event_type' => 'payment_received',
        'channel' => 'whatsapp', 'body' => 'Hola {nombre}, registramos tu pago.',
        'external_template_name' => 'pago_qa', 'external_template_locale' => 'es_MX',
        'external_template_status' => 'approved', 'enabled' => false]);
    Http::fakeSequence()
        ->push(['data' => [['name' => 'pago_qa', 'language' => 'es_MX', 'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => 'Hola {{1}}, registramos tu pago.']]]]], 200)
        ->push(['messages' => [['id' => 'wamid.test']]], 200);

    $this->post(route('comunicaciones.whatsapp.test'), ['recipient' => '+525512345678',
        'event_type' => 'payment_received'])->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Meta aceptó la prueba WhatsApp; verifica el teléfono receptor. Esto no confirma entrega.')
        ->assertSessionHas('whatsappTestMessageId', 'wamid.test');
    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/messages')
        && $request['to'] === '525512345678'
        && $request['template']['components'][0]['parameters'][0]['text'] === 'Prueba técnica');
    $audit = Activity::where('event', 'communication.test_sent')->sole();
    expect($audit->team_id)->toBe($admin->current_team_id)
        ->and($audit->sucursal_id)->toBe($admin->current_sucursal_id)
        ->and($audit->causer_id)->toBe($admin->id);
    expect(json_encode($audit->toArray()))->not->toContain('token-qa')->not->toContain('525512345678');

    $other = User::factory()->withPersonalTeam()->create();
    $this->actingAs($other)->post(route('comunicaciones.whatsapp.test'), ['recipient' => '+525512345678',
        'event_type' => 'payment_received'])->assertForbidden();
    Http::assertSentCount(2);
});

test('la prueba WhatsApp bloquea destino distinto, plantilla ausente y formato inválido sin llamar a Meta', function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    $admin = actingAsSuperAdmin();
    $this->actingAs($admin);
    config(['comunicaciones.whatsapp_test_enabled' => true,
        'comunicaciones.whatsapp_test_recipient' => '+525512345678']);
    Http::fake();
    $this->post(route('comunicaciones.whatsapp.test'), ['recipient' => '5512345678',
        'event_type' => 'payment_received'])->assertSessionHasErrors(['recipient' => 'Indica el número en formato internacional, por ejemplo +525512345678.']);
    $this->post(route('comunicaciones.whatsapp.test'), ['recipient' => '+525598765432',
        'event_type' => 'payment_received'])->assertSessionHasErrors('recipient');
    $this->post(route('comunicaciones.whatsapp.test'), ['recipient' => '+525512345678',
        'event_type' => 'payment_received'])->assertSessionHasErrors('recipient');
    Http::assertNothingSent();
    expect(Activity::where('event', 'communication.test_sent')->count())->toBe(0);
});
