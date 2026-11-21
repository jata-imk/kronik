<?php

use App\Jobs\SendCommunicationDelivery;
use App\Models\Activity;
use App\Models\CommunicationChannel;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\CommunicationRule;
use App\Services\Communications\CommunicationOrchestrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => $this->withoutVite());

function communicationReadyPayment($test): array
{
    [$actor, $credit, $data] = creditoPagableQa($test);
    $data['previa_hash'] = $test->postJson(route('creditos.pagos.preview', $credit), $data)->assertOk()->json('previa_hash');
    $test->post(route('creditos.pagos.store', $credit), $data)->assertSessionHasNoErrors();
    $event = CommunicationEvent::where('event_type', 'payment_received')->sole();
    $credit->cliente->update(['email' => 'acreditada@example.test']);
    CommunicationChannel::create(['team_id' => $credit->team_id, 'channel' => 'email', 'provider' => 'smtp',
        'sender' => 'avisos@example.test', 'eligibility' => 'approved', 'eligibility_reference' => 'QA-1', 'enabled' => true]);
    CommunicationRule::create(['team_id' => $credit->team_id, 'event_type' => 'payment_received', 'channel' => 'email',
        'enabled' => true, 'timezone' => 'America/Mexico_City', 'window_start' => '09:00:00', 'window_end' => '20:00:00',
        'daily_limit' => 1, 'subject' => 'Pago recibido', 'body' => 'Hola {nombre}, registramos tu pago.', 'template_version' => 1]);

    return [$actor, $credit, $event];
}

test('el envío externo exige consentimiento y activación doble, se acepta una sola vez sin fingir entrega', function () {
    [$actor, $credit] = communicationReadyPayment($this);
    $service = app(CommunicationOrchestrator::class);
    Mail::fake();
    expect($service->prepare())->toBe(0);
    $service->grantConsent($credit->cliente, $credit->team_id,
        ['channel' => 'email', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'EVID-1'], $actor);
    expect($service->prepare())->toBe(1)->and($service->prepare())->toBe(0);
    expect(Activity::where('event', 'communication.delivery_prepared')->count())->toBe(1);
    expect($service->process())->toBe(0);
    Mail::assertNothingSent();
    config(['comunicaciones.send_enabled' => true]);
    expect($service->process())->toBe(1)->and($service->process())->toBe(0);
    $delivery = CommunicationDelivery::sole();
    expect($delivery->fresh()->status)->toBe('queued');
    expect(Activity::where('event', 'communication.delivery_queued')->count())->toBe(1);
    (new SendCommunicationDelivery($delivery->id))->handle($service);
    (new SendCommunicationDelivery($delivery->id))->handle($service);
    Mail::assertSentCount(1);
    $delivery->refresh();
    expect($delivery->status)->toBe('accepted')->and($delivery->accepted_at)->not->toBeNull()
        ->and($delivery->delivered_at)->toBeNull()->and($delivery->read_at)->toBeNull();
    expect(Activity::where('event', 'communication.delivery_sending')->count())->toBe(1)
        ->and(Activity::where('event', 'communication.delivery_accepted')->count())->toBe(1);
    expect(json_encode(DB::table('communication_deliveries')->first(), JSON_THROW_ON_ERROR))->not->toContain('acreditada@example.test');
});

test('baja o cambio de contacto cancela un envío preparado antes de contactar al proveedor', function () {
    [$actor, $credit] = communicationReadyPayment($this);
    $service = app(CommunicationOrchestrator::class);
    $consent = $service->grantConsent($credit->cliente, $credit->team_id,
        ['channel' => 'email', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'EVID-1'], $actor);
    $service->prepare();
    $service->revokeConsent($consent, $actor);
    expect(CommunicationDelivery::sole()->status)->toBe('cancelled');
    config(['comunicaciones.send_enabled' => true]);
    Mail::fake();
    expect($service->process())->toBe(0);
    Mail::assertNothingSent();

    CommunicationEvent::create(['event_key' => 'payment:recheck:'.\Illuminate\Support\Str::uuid(),
        'event_type' => 'payment_received', 'team_id' => $credit->team_id, 'credito_id' => $credit->id,
        'cliente_id' => $credit->cliente_id, 'sucursal_id' => $credit->sucursal_id,
        'source_pago_id' => CommunicationEvent::firstOrFail()->source_pago_id]);
    $credit->cliente->update(['email' => 'acreditada@example.test']);
    $service->grantConsent($credit->cliente, $credit->team_id,
        ['channel' => 'email', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'EVID-2'], $actor);
    $service->prepare();
    $credit->cliente->update(['email' => 'nuevo@example.test']);
    $service->process();
    (new SendCommunicationDelivery(CommunicationDelivery::latest('id')->first()->id))->handle($service);
    expect(CommunicationDelivery::latest('id')->first()->status)->toBe('cancelled');
    expect(Activity::where('event', 'communication.delivery_cancelled')->count())->toBeGreaterThanOrEqual(1);
    Mail::assertNothingSent();
});

test('reglas de otro equipo y créditos sin propietario no preparan envíos', function () {
    [$actor, $credit, $event] = communicationReadyPayment($this);
    $service = app(CommunicationOrchestrator::class);
    $service->grantConsent($credit->cliente, $credit->team_id,
        ['channel' => 'email', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'EVID-1'], $actor);
    $event->update(['team_id' => null]);
    expect($service->prepare())->toBe(0);
    $event->update(['team_id' => $credit->team_id]);
    DB::table('creditos')->where('id', $credit->id)->update(['team_id' => null]);
    expect($service->prepare())->toBe(0);
});

test('la vigencia de un evento de pago se ajusta por configuración', function () {
    [, , $event] = communicationReadyPayment($this);
    $service = app(CommunicationOrchestrator::class);
    config(['comunicaciones.payment_event_validity_hours' => 1]);
    expect($service->eventCurrent($event))->toBeTrue();
    $this->travel(2)->hours();
    expect($service->eventCurrent($event))->toBeFalse();
});

test('un trabajo retrasado vuelve a preparado y un timeout queda incierto sin reintento', function () {
    [$actor, $credit] = communicationReadyPayment($this);
    $service = app(CommunicationOrchestrator::class);
    $service->grantConsent($credit->cliente, $credit->team_id,
        ['channel' => 'email', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'EVID-1'], $actor);
    $service->prepare();
    config(['comunicaciones.send_enabled' => true]);
    expect($service->process())->toBe(1);
    $delivery = CommunicationDelivery::sole();
    config(['comunicaciones.queue_stale_minutes' => 30]);
    $this->travel(16)->minutes();
    expect($service->requeueStale())->toBe(0);
    $this->travel(15)->minutes();
    expect($service->requeueStale())->toBe(1)->and($delivery->fresh()->status)->toBe('prepared');
    expect(Activity::where('event', 'communication.delivery_requeued')->count())->toBe(1);
    expect($service->process())->toBe(1);
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('timeout de prueba'));
    (new SendCommunicationDelivery($delivery->id))->handle($service);
    (new SendCommunicationDelivery($delivery->id))->handle($service);
    expect($delivery->fresh()->status)->toBe('uncertain')
        ->and(Activity::where('event', 'communication.delivery_uncertain')->count())->toBe(1);
});

test('la ventana horaria y el límite diario compartido impiden salidas adicionales', function () {
    [$actor, $credit, $event] = communicationReadyPayment($this);
    $service = app(CommunicationOrchestrator::class);
    $service->grantConsent($credit->cliente, $credit->team_id,
        ['channel' => 'email', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'EVID-1'], $actor);
    $rule = CommunicationRule::sole();
    $rule->update(['window_start' => '00:00:00', 'window_end' => '00:00:01']);
    $service->prepare();
    config(['comunicaciones.send_enabled' => true]);
    $service->process();
    Mail::fake();
    $first = CommunicationDelivery::sole();
    expect($service->send($first->id))->toBeFalse();
    Mail::assertNothingSent();

    $rule->update(['window_start' => '00:00:00', 'window_end' => '23:59:59']);
    expect($service->send($first->id))->toBeTrue();
    Mail::assertSentCount(1);

    CommunicationEvent::create([
        'event_key' => 'payment:daily-limit:'.\Illuminate\Support\Str::uuid(),
        'event_type' => 'payment_received', 'team_id' => $credit->team_id, 'credito_id' => $credit->id,
        'cliente_id' => $credit->cliente_id, 'sucursal_id' => $credit->sucursal_id,
        'source_pago_id' => $event->source_pago_id,
    ]);
    expect($service->prepare())->toBe(1);
    expect($service->process())->toBe(1);
    $second = CommunicationDelivery::latest('id')->firstOrFail();
    expect($service->send($second->id))->toBeFalse()
        ->and($second->fresh()->status)->toBe('queued');
    Mail::assertSentCount(1);
});

test('una plantilla de Meta distinta a la aprobada localmente se rechaza sin publicar mensaje', function () {
    [$actor, $credit] = communicationReadyPayment($this);
    CommunicationChannel::where('team_id', $credit->team_id)->where('channel', 'email')->update(['enabled' => false]);
    CommunicationChannel::create(['team_id' => $credit->team_id, 'channel' => 'whatsapp', 'provider' => 'meta_cloud',
        'sender' => '123456', 'eligibility' => 'approved', 'enabled' => true,
        'secret' => json_encode(['token' => 'token-qa', 'api_version' => 'v27.0', 'waba_id' => '654321',
            'phone_number_id' => '123456', 'app_secret' => 'app-qa', 'verify_token' => 'verify-qa'])]);
    CommunicationRule::create(['team_id' => $credit->team_id, 'event_type' => 'payment_received', 'channel' => 'whatsapp',
        'enabled' => true, 'timezone' => 'America/Mexico_City', 'window_start' => '09:00:00', 'window_end' => '20:00:00',
        'daily_limit' => 1, 'body' => 'Hola {nombre}, pago registrado.', 'template_version' => 1,
        'external_template_name' => 'pago_qa', 'external_template_locale' => 'es_MX', 'external_template_status' => 'approved']);
    $service = app(CommunicationOrchestrator::class);
    $service->grantConsent($credit->cliente, $credit->team_id,
        ['channel' => 'whatsapp', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'EVID-1'], $actor);
    expect($service->prepare())->toBe(1);
    config(['comunicaciones.send_enabled' => true]);
    $service->process();
    Http::fake(['*/message_templates*' => Http::response(['data' => [['name' => 'pago_qa', 'language' => 'es_MX',
        'status' => 'APPROVED', 'components' => [['type' => 'BODY', 'text' => 'Texto diferente']]]]], 200)]);
    (new SendCommunicationDelivery(CommunicationDelivery::sole()->id))->handle($service);
    expect(CommunicationDelivery::sole()->status)->toBe('failed')
        ->and(Activity::where('event', 'communication.delivery_failed')->count())->toBe(1);
    Http::assertSentCount(1);
});
