<?php

use App\Models\Activity;
use App\Models\CommunicationChannel;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Communications\LabsMobileSmsTransport;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    config(['comunicaciones.sms.username' => 'PRIVATE-SMS-USERNAME', 'comunicaciones.sms.token' => 'PRIVATE-SMS-TOKEN',
        'comunicaciones.sms.sender' => 'PRIVATE123', 'comunicaciones.sms.rate_cache_store' => 'array']);
    Http::preventStrayRequests();
    Http::fake(fn () => null);
});

test('SMS guarda solo decisiones operativas y muestra presencia sin secretos ni remitente técnico', function () {
    $admin = actingAsSuperAdmin();
    $this->actingAs($admin)->put(route('comunicaciones.channels.update'), ['channel' => 'sms',
        'enabled' => false, 'eligibility' => 'testing'])->assertSessionHasNoErrors();
    $channel = CommunicationChannel::sole();
    expect($channel->provider)->toBe('labsmobile')->and($channel->getRawOriginal('secret'))->toBeNull()->and($channel->sender)->toBeNull();
    $response = $this->get(route('comunicaciones.index'))->assertOk();
    expect($response->getContent())->not->toContain('PRIVATE-SMS-USERNAME', 'PRIVATE-SMS-TOKEN', 'PRIVATE123');
    $response->assertInertia(fn (Assert $page) => $page->where('smsConfiguration.configured', true)
        ->where('smsConfiguration.sending_enabled', false)->missing('channels.0.sender')->missing('channels.0.secret'));
    expect(Activity::where('event', 'communication.channel_configured')->sole()->properties->toJson())
        ->not->toContain('PRIVATE-SMS-TOKEN');
});

test('backend rechaza secretos y remitente SMS en español y no los conserva en old input', function () {
    $admin = actingAsSuperAdmin();
    $response = $this->actingAs($admin)->from('/comunicaciones')->put(route('comunicaciones.channels.update'),
        ['channel' => 'sms', 'sender' => 'BAD-SENDER', 'secret' => 'DO-NOT-FLASH', 'token' => 'DO-NOT-FLASH-TOKEN',
            'enabled' => false, 'eligibility' => 'testing']);
    $response->assertSessionHasErrors(['sender' => 'El remitente SMS se configura en el entorno.',
        'secret' => 'Las credenciales SMS se configuran en el entorno.']);
    expect(session()->get('_old_input'))->not->toHaveKeys(['secret', 'token']);
    $this->assertDatabaseCount('communication_channels', 0);
    Http::assertNothingSent();
});

test('no reutiliza configuraciones ni secretos anteriores aunque su cifrado sea ilegible', function () {
    $admin = actingAsSuperAdmin();
    $channel = CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'sms', 'provider' => 'infobip']);
    \Illuminate\Support\Facades\DB::table('communication_channels')->where('id', $channel->id)->update(['secret' => 'not-decryptable']);
    $this->actingAs($admin)->get(route('comunicaciones.index'))->assertOk();
    $this->put(route('comunicaciones.channels.update'), ['channel' => 'sms', 'enabled' => false, 'eligibility' => 'testing'])
        ->assertSessionHasErrors('channel');
    $this->post(route('comunicaciones.connection.check'), ['channel' => 'sms'])->assertSessionHasErrors('channel');
    expect($channel->fresh()->getRawOriginal('secret'))->toBe('not-decryptable');
    Http::assertNothingSent();
});

test('la conexión valida código y saldo sin enviar SMS y limpia comprobaciones fallidas', function ($body, $expected) {
    $admin = actingAsSuperAdmin();
    $channel = CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'sms',
        'provider' => 'labsmobile', 'enabled' => false, 'eligibility' => 'testing', 'checked_at' => now()->subDay()]);
    Http::fake(['api.labsmobile.com/*' => Http::response($body, 200)]);
    $this->actingAs($admin)->post(route('comunicaciones.connection.check'), ['channel' => 'sms'])->assertSessionHasNoErrors();
    expect($channel->fresh()->checked_at !== null)->toBe($expected);
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->method() === 'GET' && $request->url() === 'https://api.labsmobile.com/json/balance');
    $audit = Activity::where('event', 'communication.test_sent')->sole();
    expect($audit->causer_id)->toBe($admin->id)->and($audit->team_id)->toBe($admin->current_team_id)
        ->and($audit->sucursal_id)->toBe($admin->current_sucursal_id)
        ->and($audit->properties['result'])->toBe($expected ? 'accepted' : 'failed');
    expect($audit->properties->toJson())->not->toContain('PRIVATE-SMS-TOKEN', 'PRIVATE-SMS-USERNAME');
})->with([
    'cero es válido' => [['code' => 0, 'credits' => '0'], true],
    'código string' => [['code' => '0', 'credits' => 12.5], true],
    'saldo sin código' => [['credits' => 100], false],
    'rechazo con saldo' => [['code' => 403, 'credits' => 100], false],
    'sin saldo' => [['code' => 0], false],
    'saldo negativo' => [['code' => 0, 'credits' => -1], false],
    'saldo inválido' => [['code' => 0, 'credits' => 'unknown'], false],
    'booleano' => [['code' => false, 'credits' => true], false],
]);

test('URL no autorizada o credenciales ausentes bloquean conexión y envío', function () {
    config(['comunicaciones.sms.base_url' => 'https://attacker.example.test/json']);
    expect(app(LabsMobileSmsTransport::class)->checkConnection())->toBeFalse();
    config(['comunicaciones.sms.base_url' => 'https://api.labsmobile.com/json', 'comunicaciones.sms.token' => null]);
    expect(app(LabsMobileSmsTransport::class)->checkConnection())->toBeFalse();
    Http::assertNothingSent();
});

test('solo permisos del equipo permiten configuración y comprobación pero consulta funciona sola', function () {
    $admin = actingAsSuperAdmin();
    CommunicationChannel::create(['team_id' => $admin->current_team_id, 'channel' => 'sms', 'provider' => 'labsmobile']);
    $reader = User::factory()->create(['current_team_id' => $admin->current_team_id, 'current_sucursal_id' => $admin->current_sucursal_id]);
    grantTeamPermissions($reader, $admin->currentTeam, ['read comunicaciones']);
    $this->actingAs($reader)->get(route('comunicaciones.index'))->assertOk();
    $this->put(route('comunicaciones.channels.update'), ['channel' => 'sms', 'enabled' => false, 'eligibility' => 'testing'])->assertForbidden();
    $this->post(route('comunicaciones.connection.check'), ['channel' => 'sms'])->assertForbidden();
    Http::assertNothingSent();
});

test('la configuración y su auditoría comparten rollback', function () {
    $admin = actingAsSuperAdmin();
    $this->mock(ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('synthetic-audit-failure'));
    $this->withoutExceptionHandling();
    try {
        $this->actingAs($admin)->put(route('comunicaciones.channels.update'), ['channel' => 'sms', 'enabled' => false, 'eligibility' => 'testing']);
    } catch (RuntimeException) {
        $this->assertDatabaseCount('communication_channels', 0);

        return;
    }
    $this->fail('Se esperaba el fallo de auditoría.');
});
