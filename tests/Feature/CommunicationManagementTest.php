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

test('la consulta distingue consentimiento vigente contacto cambiado y revocado sin exponer el hash', function (string $channel, bool $removeContact) {
    $admin = actingAsSuperAdmin();
    $client = Cliente::factory()->create(['sucursal_id' => $admin->current_sucursal_id,
        'email' => 'original@example.test', 'telefono_codigo_pais' => '52', 'telefono' => '5512345678']);
    $this->actingAs($admin);
    $this->post(route('comunicaciones.consents.store', $client), ['channel' => $channel, 'purpose' => 'operational',
        'source' => 'contrato', 'evidence_reference' => 'evidencia-reservada-i4'])->assertSessionHasNoErrors();
    $consent = CommunicationConsent::sole();
    $audits = Activity::count();
    $this->get(route('clientes.show', $client))->assertInertia(fn (Assert $page) => $page
        ->where('communicationConsents.0.effective_status', 'current')->missing('communicationConsents.0.contact_hash'));
    $field = $channel === 'email' ? 'email' : 'telefono';
    $client->update([$field => $removeContact ? '' : ($channel === 'email' ? 'nuevo@example.test' : '5598765432')]);
    $this->get(route('clientes.show', $client))->assertInertia(fn (Assert $page) => $page
        ->where('communicationConsents.0.effective_status', 'contact_changed')->missing('communicationConsents.0.evidence_reference'));
    $response = $this->get(route('comunicaciones.index'))->assertInertia(fn (Assert $page) => $page
        ->where('consents.0.effective_status', 'contact_changed')->missing('consents.0.contact_hash')->missing('consents.0.cliente'));
    expect($response->getContent())->not->toContain($consent->contact_hash, 'evidencia-reservada-i4', 'nuevo@example.test', '5598765432');
    expect(Activity::count())->toBe($audits)->and($consent->fresh()->revoked_at)->toBeNull();
    $this->patch(route('comunicaciones.consents.revoke', [$client, $consent]))->assertSessionHasNoErrors();
    $this->get(route('comunicaciones.index'))->assertInertia(fn (Assert $page) => $page
        ->where('consents.0.effective_status', 'revoked'));
})->with(['email', 'sms', 'whatsapp'])->with([false, true]);

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
    $data['notification_template_id'] = notificationTemplateQa($data)->id;
    $this->put(route('comunicaciones.rules.update'), [...$data, 'notification_template_id' => null])->assertSessionHasErrors('notification_template_id');
    $this->put(route('comunicaciones.rules.update'), [...$data, 'window_end' => '08:00'])
        ->assertSessionHasErrors(['window_end' => 'El campo fin de la ventana debe ser posterior a inicio de la ventana.']);
    $this->put(route('comunicaciones.rules.update'), $data)->assertSessionHasNoErrors();
    expect(CommunicationRule::sole()->template_version)->toBe(1);
    $replacement = notificationTemplateQa([...$data, 'body' => 'Gracias por tu pago, {nombre}.']);
    $this->put(route('comunicaciones.rules.update'), [...$data, 'notification_template_id' => $replacement->id])->assertSessionHasNoErrors();
    expect(CommunicationRule::sole()->template_version)->toBe(2);
    expect(Activity::where('event', 'communication.rule_configured')->count())->toBe(2);
    $dueRule = [...$data, 'event_type' => 'upcoming_due', 'lead_days' => 0];
    $dueRule['notification_template_id'] = notificationTemplateQa($dueRule)->id;
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
    CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'sms', 'provider' => 'labsmobile',
        'sender' => 'Kronik', 'eligibility' => 'testing', 'enabled' => false,
        'secret' => null]);
    CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'whatsapp', 'provider' => 'meta_cloud',
        'sender' => '123456', 'eligibility' => 'testing', 'enabled' => false,
        'secret' => json_encode(['token' => 'test', 'api_version' => 'v27.0', 'waba_id' => '654321',
            'phone_number_id' => '123456', 'app_secret' => 'firma', 'verify_token' => 'verificar'])]);
    config(['comunicaciones.sms.username' => 'qa@example.test', 'comunicaciones.sms.token' => 'synthetic', 'comunicaciones.sms.rate_cache_store' => 'array']);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.labsmobile.com/json/balance' => Http::response(['code' => '0', 'credits' => '0'], 200),
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

test('WhatsApp conserva texto local validación y cancelación diferida sin usar catálogo global', function () {
    Http::preventStrayRequests();
    Mail::fake();
    [$actor, $credit, $event] = communicationReadyPayment($this);
    $this->actingAs($actor);
    $data = ['event_type' => 'payment_received', 'channel' => 'whatsapp', 'enabled' => true,
        'timezone' => 'America/Mexico_City', 'window_start' => '09:00', 'window_end' => '20:00', 'daily_limit' => 1,
        'body' => 'Aviso {} para {nombre}.', 'external_template_name' => 'pago_qa',
        'external_template_locale' => 'es_MX', 'external_template_status' => 'approved'];
    $this->put(route('comunicaciones.rules.update'), $data)->assertSessionHasNoErrors();
    $rule = CommunicationRule::where('channel', 'whatsapp')->sole();
    $delivery = App\Models\CommunicationDelivery::create(['team_id' => $event->team_id, 'communication_event_id' => $event->id,
        'cliente_id' => $credit->cliente_id, 'credito_id' => $credit->id, 'channel' => 'whatsapp',
        'recipient_hash' => str_repeat('a', 64), 'template_version' => 1, 'status' => 'prepared']);
    $this->put(route('comunicaciones.rules.update'), [...$data, 'body' => 'Otro aviso {} para {nombre}.'])->assertSessionHasNoErrors();
    expect($rule->fresh()->notification_template_id)->toBeNull()->and($rule->fresh()->template_version)->toBe(2)
        ->and($delivery->fresh()->status)->toBe('prepared')->and(App\Models\NotificationTemplate::count())->toBe(1);
    Http::assertNothingSent();
    Mail::assertNothingSent();
});

test('permite desactivar reglas sin versión activa pero impide volver a habilitarlas', function ($channel, $missing) {
    Http::preventStrayRequests();
    Mail::fake();
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $template = $missing ? null : notificationTemplateQa(['channel' => $channel], 'retired');
    $data = ['event_type' => 'payment_received', 'channel' => $channel, 'enabled' => true,
        'timezone' => 'America/Mexico_City', 'window_start' => '09:00', 'window_end' => '20:00',
        'daily_limit' => 1, 'notification_template_id' => $template?->id];
    $rule = CommunicationRule::create([...$data, 'team_id' => $actor->current_team_id, 'body' => '']);
    $this->put(route('comunicaciones.rules.update'), [...$data, 'enabled' => false])->assertSessionHasNoErrors();
    expect($rule->fresh()->enabled)->toBeFalse()
        ->and($rule->fresh()->notification_template_id)->toBe($template?->id);
    expect(Activity::where('event', 'communication.rule_configured')->count())->toBe(1);
    $this->put(route('comunicaciones.rules.update'), $data)->assertSessionHasErrors('notification_template_id');
    expect($rule->fresh()->enabled)->toBeFalse();
    Http::assertNothingSent();
    Mail::assertNothingSent();
})->with(['sms', 'email'])->with([true, false]);
