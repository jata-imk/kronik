<?php

use App\Jobs\SendCommunicationDelivery;
use App\Models\Activity;
use App\Models\CommunicationChannel;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\CommunicationRule;
use App\Services\Communications\CommunicationOrchestrator;
use App\Services\Communications\CommunicationWebhookService;
use App\Services\Communications\LabsMobileRequestLimiter;
use App\Services\Communications\LabsMobileSmsTransport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();
    config(['comunicaciones.send_enabled' => false, 'comunicaciones.sms_send_enabled' => false,
        'comunicaciones.sms.username' => 'synthetic@example.test', 'comunicaciones.sms.token' => 'SYNTHETIC-API-TOKEN',
        'comunicaciones.sms.sender' => 'KronikQA', 'comunicaciones.sms.rate_cache_store' => 'array']);
    Http::preventStrayRequests();
    Http::fake(fn () => null);
    Queue::fake();
});

function labsMobileReadyPayment($test, string $body = 'Hola {nombre}, registramos tu pago.'): array
{
    [$actor, $credit, $data] = creditoPagableQa($test);
    $data['previa_hash'] = $test->postJson(route('creditos.pagos.preview', $credit), $data)->assertOk()->json('previa_hash');
    $test->post(route('creditos.pagos.store', $credit), $data)->assertSessionHasNoErrors();
    $event = CommunicationEvent::where('event_type', 'payment_received')->sole();
    $credit->cliente->update(['telefono_codigo_pais' => '52', 'telefono' => '5512345678']);
    $channel = CommunicationChannel::create(['team_id' => $credit->team_id, 'channel' => 'sms', 'provider' => 'labsmobile',
        'eligibility' => 'approved', 'enabled' => true]);
    $rule = communicationRuleQa(['team_id' => $credit->team_id, 'event_type' => 'payment_received', 'channel' => 'sms',
        'enabled' => true, 'timezone' => 'America/Mexico_City', 'window_start' => '00:00:00', 'window_end' => '23:59:59',
        'daily_limit' => 5, 'body' => $body]);
    $service = app(CommunicationOrchestrator::class);
    $service->grantConsent($credit->cliente, $credit->team_id,
        ['channel' => 'sms', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'SYNTHETIC-EVIDENCE'], $actor);
    expect($service->prepare())->toBe(1);

    return [$actor, $credit, $event, $channel, $rule, CommunicationDelivery::sole()];
}

test('LabsMobile envía JSON Basic Auth y correlación persistida una sola vez sin callback', function () {
    [$actor, $credit, $event, , , $delivery] = labsMobileReadyPayment($this);
    expect($delivery->provider)->toBe('labsmobile')->and(strlen($delivery->provider_correlation_id))->toBe(20);
    Http::fake(function ($request) use ($delivery) {
        expect($delivery->fresh()->status)->toBe('sending')
            ->and($delivery->fresh()->provider_correlation_id)->toBe($request['subid']);

        return Http::response(['code' => '0', 'subid' => $request['subid']], 200);
    });
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true]);
    $service = app(CommunicationOrchestrator::class);
    expect($service->process())->toBe(1)->and($service->send($delivery->id))->toBeTrue();
    $this->travel(1)->seconds();
    expect($service->send($delivery->id))->toBeFalse();
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://api.labsmobile.com/json/send'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('synthetic@example.test:SYNTHETIC-API-TOKEN'))
        && $request['recipient'] === [['msisdn' => '525512345678']]
        && $request['tpoa'] === 'KronikQA' && ! isset($request['ackurl'])
        && $request['subid'] === $delivery->provider_correlation_id);
    expect($delivery->fresh()->status)->toBe('accepted')->and($delivery->fresh()->provider_message_id)->toBe($delivery->provider_correlation_id)
        ->and($delivery->fresh()->delivered_at)->toBeNull()->and($delivery->fresh()->read_at)->toBeNull();
    $audit = Activity::where('event', 'communication.delivery_accepted')->sole();
    expect($audit->team_id)->toBe($credit->team_id)->and($audit->sucursal_id)->toBe($event->sucursal_id)
        ->and($audit->causer_id)->toBeNull();
    expect(Activity::where('event', 'like', 'communication.delivery_%')->get()->toJson())
        ->not->toContain('SYNTHETIC-API-TOKEN', '525512345678', 'registramos tu pago');
});

test('cada barrera SMS bloquea cola y jobs existentes sin afectar las otras barreras', function ($global, $sms) {
    [, , , , , $delivery] = labsMobileReadyPayment($this);
    config(['comunicaciones.send_enabled' => $global, 'comunicaciones.sms_send_enabled' => $sms]);
    $service = app(CommunicationOrchestrator::class);
    expect($service->process())->toBe(0);
    $delivery->update(['status' => 'queued']);
    (new SendCommunicationDelivery($delivery->id))->handle($service);
    expect($delivery->fresh()->status)->toBe('queued')->and($delivery->fresh()->attempted_at)->toBeNull();
    Http::assertNothingSent();
})->with([[false, false], [true, false], [false, true]]);

test('texto expandido excesivo se cancela sin HTTP ni truncamiento', function () {
    [, $credit, , , $rule, $delivery] = labsMobileReadyPayment($this, '{nombre}{nombre}{nombre}');
    $credit->cliente->update(['primer_nombre' => str_repeat('á', 60)]);
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true]);
    $service = app(CommunicationOrchestrator::class);
    $service->process();
    expect($service->send($delivery->id))->toBeFalse()->and($delivery->fresh()->reason)->toBe('sms_content_invalid')
        ->and($delivery->fresh()->attempted_at)->toBeNull();
    Http::assertNothingSent();
});

test('salidas sin proveedor propio no se reinterpretan al cambiar la cuenta del canal', function () {
    [, , , , , $delivery] = labsMobileReadyPayment($this);
    $delivery->update(['provider' => null, 'provider_correlation_id' => null, 'status' => 'queued']);
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true]);
    expect(app(CommunicationOrchestrator::class)->send($delivery->id))->toBeFalse()
        ->and($delivery->fresh()->status)->toBe('cancelled');
    Http::assertNothingSent();
});

test('el límite compartido pospone antes del intento y conserva correlación', function () {
    [, , , , , $delivery] = labsMobileReadyPayment($this);
    $correlation = $delivery->provider_correlation_id;
    Http::fake(['api.labsmobile.com/json/balance' => Http::response([], 200)]);
    expect(app(LabsMobileSmsTransport::class)->checkConnection())->toBeFalse(); // A fake without valid balance still consumes a request slot.
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true]);
    $service = app(CommunicationOrchestrator::class);
    $service->process();
    expect($service->send($delivery->id))->toBeFalse()->and($delivery->fresh()->status)->toBe('prepared')
        ->and($delivery->fresh()->attempted_at)->toBeNull()->and($delivery->fresh()->provider_correlation_id)->toBe($correlation);
    Http::assertSentCount(1);
    expect(Activity::where('event', 'communication.delivery_sending')->count())->toBe(0);
    $this->travel(1)->seconds();
    Http::fake(fn ($request) => Http::response(['code' => 0, 'subid' => $request['subid']], 200));
    $service->process();
    expect($service->send($delivery->id))->toBeTrue();
});

test('HTTP ambiguo queda incierto y rechazo explícito queda fallido sin reenvío', function ($body, $http, $expected) {
    $actor = actingAsSuperAdmin();
    $channel = CommunicationChannel::create(['team_id' => $actor->current_team_id, 'channel' => 'sms',
        'provider' => 'labsmobile', 'enabled' => true, 'eligibility' => 'approved']);
    $rule = new CommunicationRule;
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true]);
    Http::fake(['api.labsmobile.com/*' => Http::response($body, $http)]);
    $receipt = app(LabsMobileSmsTransport::class)->send($channel, $rule, '+525512345678', '', 'Pago recibido.', [], str_repeat('a', 20));
    expect($receipt['status'])->toBe($expected);
    Http::assertSentCount(1);
})->with([
    'aceptado' => [['code' => '0', 'subid' => str_repeat('a', 20)], 200, 'accepted'],
    'sin saldo' => [['code' => '35', 'subid' => str_repeat('a', 20)], 200, 'failed'],
    'sin código' => [['subid' => str_repeat('a', 20)], 200, 'uncertain'],
    'sin correlación' => [['code' => 0], 200, 'uncertain'],
    'otra correlación' => [['code' => 0, 'subid' => 'other'], 200, 'uncertain'],
    'código booleano' => [['code' => false], 200, 'uncertain'],
    'HTML' => ['not-json', 200, 'uncertain'],
    'error servidor' => [[], 500, 'uncertain'],
    'timeout HTTP' => [[], 408, 'uncertain'],
    'límite remoto' => [[], 429, 'uncertain'],
    'credencial rechazada' => [[], 401, 'failed'],
    'redirección' => [[], 302, 'uncertain'],
]);

test('timeout de red conserva proveedor y correlación sin repetir el intento', function () {
    [, , , , , $delivery] = labsMobileReadyPayment($this);
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true]);
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('synthetic-timeout'));
    $service = app(CommunicationOrchestrator::class);
    $service->process();
    $service->send($delivery->id);
    $this->travel(1)->seconds();
    $service->send($delivery->id);
    expect($delivery->fresh()->status)->toBe('uncertain')->and($delivery->fresh()->provider)->toBe('labsmobile')
        ->and($delivery->fresh()->provider_correlation_id)->toBe($delivery->provider_correlation_id);
    expect(Activity::where('event', 'communication.delivery_sending')->count())->toBe(1)
        ->and(Activity::where('event', 'communication.delivery_uncertain')->count())->toBe(1);
});

test('el limitador usa exclusión de cuenta y falla cerrado sin almacén compartido', function () {
    $limiter = app(LabsMobileRequestLimiter::class);
    $key = 'labsmobile:'.hash('sha256', 'synthetic@example.test');
    $lock = Cache::store('array')->lock($key.':lock', 120);
    $lock->get();
    expect($limiter->run(fn () => 'unexpected'))->toBeNull();
    $lock->release();
    expect($limiter->run(fn () => 'first'))->toBe('first')
        ->and($limiter->run(fn () => 'second'))->toBeNull();
    $this->travel(1)->seconds();
    expect($limiter->run(fn () => false))->toBeFalse();
    config(['comunicaciones.sms.rate_cache_store' => 'missing-store']);
    expect($limiter->run(fn () => 'unexpected'))->toBeNull();
});

test('un webhook Meta no puede promover una salida SMS con el mismo identificador', function () {
    [, $credit, , , , $delivery] = labsMobileReadyPayment($this);
    $delivery->update(['status' => 'accepted', 'provider_message_id' => 'collision']);
    app(CommunicationWebhookService::class)->apply($credit->team_id, 'meta_cloud', 'collision', 'read', '100');
    app(CommunicationWebhookService::class)->reconcile($delivery);
    expect($delivery->fresh()->status)->toBe('accepted')
        ->and(DB::table('communication_webhook_events')->whereNotNull('communication_delivery_id')->count())->toBe(0);
});

test('el limitador no oculta un fallo de dominio como si fuera saturación', function () {
    expect(fn () => app(LabsMobileRequestLimiter::class)->run(fn () => throw new RuntimeException('synthetic-domain-failure')))
        ->toThrow(RuntimeException::class, 'synthetic-domain-failure');
});

test('una escritura de caché rechazada sin excepción también impide HTTP', function () {
    $cache = Mockery::mock(\Illuminate\Cache\Repository::class);
    $cache->shouldReceive('lock')->once()->andReturn(new class
    {
        public function get(callable $callback): mixed
        {
            return $callback();
        }
    });
    $cache->shouldReceive('get')->once()->andReturn(0);
    $cache->shouldReceive('put')->once()->andReturn(false);
    config(['comunicaciones.sms.rate_cache_store' => 'unwritable']);
    Cache::shouldReceive('store')->with('unwritable')->once()->andReturn($cache);
    $called = false;
    expect(app(LabsMobileRequestLimiter::class)->run(function () use (&$called) {
        $called = true;

        return true;
    }))->toBeNull();
    expect($called)->toBeFalse();
    Http::assertNothingSent();
});
