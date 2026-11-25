<?php

use App\Models\Activity;
use App\Models\CommunicationChannel;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\CommunicationRule;
use App\Services\ActivityLogService;
use App\Services\Communications\CommunicationOrchestrator;
use App\Services\Communications\LabsMobileAcknowledgement;
use App\Services\Communications\LabsMobileAcknowledgementService;
use App\Services\Communications\LabsMobileSmsTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();
    Http::preventStrayRequests();
    Http::fake(fn () => null);
    Queue::fake();
    config(['comunicaciones.send_enabled' => false, 'comunicaciones.sms_send_enabled' => false,
        'comunicaciones.sms.username' => 'synthetic@example.test', 'comunicaciones.sms.token' => 'SYNTHETIC-API-TOKEN',
        'comunicaciones.sms.sender' => 'KronikQA', 'comunicaciones.sms.rate_cache_store' => 'array']);
});

function labsAckFixture($test): array
{
    [$actor, $credit, $data] = creditoPagableQa($test);
    $test->travelTo(now()->utc());
    $data['previa_hash'] = $test->postJson(route('creditos.pagos.preview', $credit), $data)->assertOk()->json('previa_hash');
    $test->post(route('creditos.pagos.store', $credit), $data)->assertSessionHasNoErrors();
    $event = CommunicationEvent::where('event_type', 'payment_received')->sole();
    $credit->cliente->update(['telefono_codigo_pais' => '52', 'telefono' => '5512345678']);
    CommunicationChannel::create(['team_id' => $credit->team_id, 'channel' => 'sms', 'provider' => 'labsmobile',
        'eligibility' => 'approved', 'enabled' => true]);
    CommunicationRule::create(['team_id' => $credit->team_id, 'event_type' => 'payment_received', 'channel' => 'sms',
        'enabled' => true, 'timezone' => 'America/Mexico_City', 'window_start' => '00:00:00', 'window_end' => '23:59:59',
        'daily_limit' => 5, 'body' => 'Hola {nombre}, registramos tu pago.']);
    $service = app(CommunicationOrchestrator::class);
    $service->grantConsent($credit->cliente, $credit->team_id,
        ['channel' => 'sms', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'SYNTHETIC-EVIDENCE'], $actor);
    $service->prepare();
    $delivery = CommunicationDelivery::sole();
    $delivery->update(['status' => 'sending', 'attempted_at' => now()->subMinute()]);

    return [$actor, $credit, $event, $delivery];
}

function labsAckEvidence(CommunicationDelivery $delivery, array $changes = []): LabsMobileAcknowledgement
{
    return LabsMobileAcknowledgement::fromPayload(array_replace([
        'subid' => $delivery->provider_correlation_id, 'msisdn' => '525512345678',
        'acklevel' => 'handset', 'status' => 'ok', 'desc' => 'DELIVRD',
        'timestamp' => now()->utc()->format('Y-m-d H:i:s'),
    ], $changes));
}

test('persiste niveles sin fingir entrega de operador y concilia duplicados y desorden una sola vez', function () {
    [, , , $delivery] = labsAckFixture($this);
    $service = app(LabsMobileAcknowledgementService::class);
    foreach (['gateway', 'operator', 'handset'] as $level) {
        expect($service->record(labsAckEvidence($delivery, ['acklevel' => $level, 'desc' => null])))->toBeTrue();
        expect($delivery->fresh()->status)->toBe($level === 'handset' ? 'delivered' : 'accepted')
            ->and($delivery->fresh()->delivered_at !== null)->toBe($level === 'handset');
    }
    expect($service->record(labsAckEvidence($delivery, ['desc' => null])))->toBeFalse();
    $service->record(labsAckEvidence($delivery, ['acklevel' => 'operator', 'desc' => null,
        'timestamp' => now()->subSeconds(10)->utc()->format('Y-m-d H:i:s')]));
    $service->record(labsAckEvidence($delivery, ['acklevel' => 'error', 'status' => 'ko', 'desc' => 'EXPIRED']));
    $service->reconcile($delivery);
    $service->reconcile($delivery);
    expect($delivery->fresh()->status)->toBe('delivered')->and($delivery->fresh()->read_at)->toBeNull()
        ->and($delivery->fresh()->accepted_at)->toBeNull()->and($delivery->fresh()->provider_message_id)->toBeNull()
        ->and(DB::table('communication_webhook_events')->count())->toBe(5)
        ->and(DB::table('communication_webhook_events')->whereNull('applied_at')->count())->toBe(0)
        ->and(Activity::where('event', 'communication.delivery_status_updated')->count())->toBe(3);
    Http::assertNothingSent();
});

test('confirmación handset resuelve un fallo anterior conservando la hora del proveedor', function () {
    [, , , $delivery] = labsAckFixture($this);
    $service = app(LabsMobileAcknowledgementService::class);
    $service->record(labsAckEvidence($delivery, ['acklevel' => 'error', 'status' => 'ko', 'desc' => 'UNDELIV']));
    expect($delivery->fresh()->status)->toBe('failed');
    $time = now()->subSeconds(20)->utc()->format('Y-m-d H:i:s');
    $service->record(labsAckEvidence($delivery, ['timestamp' => $time]));
    expect($delivery->fresh()->status)->toBe('delivered')
        ->and($delivery->fresh()->delivered_at->utc()->format('Y-m-d H:i:s'))->toBe($time);
});

test('no asocia evidencia a otro canal proveedor estado o contexto original', function ($changes) {
    [, , $event, $delivery] = labsAckFixture($this);
    $ack = labsAckEvidence($delivery);
    if (isset($changes['event_team'])) {
        $event->update(['team_id' => null]);
        unset($changes['event_team']);
    }
    $delivery->update($changes);
    expect(app(LabsMobileAcknowledgementService::class)->record($ack))->toBeFalse();
    $this->assertDatabaseCount('communication_webhook_events', 0);
    expect(Activity::where('event', 'communication.delivery_status_updated')->count())->toBe(0);
})->with([
    [['channel' => 'email']], [['channel' => 'whatsapp']], [['provider' => 'infobip']], [['provider' => null]],
    [['attempted_at' => null]], [['status' => 'prepared']], [['status' => 'queued']], [['status' => 'cancelled']],
    [['provider_message_id' => 'other-id']], [['event_team' => true]],
]);

test('correlación ajena teléfono cambiado o acuse anterior al intento no alteran la salida', function ($changes) {
    [, , , $delivery] = labsAckFixture($this);
    expect(app(LabsMobileAcknowledgementService::class)->record(labsAckEvidence($delivery, $changes)))->toBeFalse();
    $this->assertDatabaseCount('communication_webhook_events', 0);
    expect($delivery->fresh()->status)->toBe('sending');
})->with([
    [['subid' => str_repeat('0', 20)]], [['msisdn' => '525599999999']], [['msisdn' => '5215512345678']],
    [['timestamp' => '2020-01-01 00:00:00']],
]);

test('usa el contacto y contexto del intento aunque cambien cliente y preferencias del actor', function () {
    [$actor, $credit, $event, $delivery] = labsAckFixture($this);
    $credit->cliente->update(['telefono' => '5599999999']);
    $actor->update(['current_team_id' => null, 'current_sucursal_id' => null]);
    expect(app(LabsMobileAcknowledgementService::class)->record(labsAckEvidence($delivery)))->toBeTrue();
    $audit = Activity::where('event', 'communication.delivery_status_updated')->sole();
    expect($audit->causer_id)->toBeNull()->and($audit->subject_id)->toBe($delivery->id)
        ->and($audit->team_id)->toBe($delivery->team_id)->and($audit->sucursal_id)->toBe($event->sucursal_id)
        ->and($audit->properties['result'])->toBe('handset');
    expect(json_encode(DB::table('communication_webhook_events')->first(), JSON_THROW_ON_ERROR))
        ->not->toContain('525512345678', 'SYNTHETIC-API-TOKEN', 'registramos tu pago');
    expect($audit->toJson())->not->toContain('525512345678', 'SYNTHETIC-API-TOKEN', 'registramos tu pago');
    expect(array_diff(array_keys($audit->properties->all()),
        ['state', 'provider', 'result', 'ip', 'user_agent', 'request_id']))->toBe([]);
});

test('un fallo de auditoría revierte acuse estado y aplicación y permite reintentar', function () {
    [, , , $delivery] = labsAckFixture($this);
    $activity = Mockery::mock(ActivityLogService::class);
    $activity->shouldReceive('log')->once()->andThrow(new RuntimeException('synthetic-audit-failure'));
    $service = new LabsMobileAcknowledgementService($activity);
    expect(fn () => $service->record(labsAckEvidence($delivery)))->toThrow(RuntimeException::class, 'synthetic-audit-failure');
    expect($delivery->fresh()->status)->toBe('sending')->and($delivery->fresh()->delivered_at)->toBeNull();
    $this->assertDatabaseCount('communication_webhook_events', 0);
    expect(app(LabsMobileAcknowledgementService::class)->record(labsAckEvidence($delivery)))->toBeTrue();
});

test('acuse durante HTTP conserva evidencia ante aceptación o respuesta incierta sin reenviar', function ($response) {
    [, , , $delivery] = labsAckFixture($this);
    $delivery->update(['status' => 'prepared', 'attempted_at' => null]);
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true]);
    $calls = 0;
    Http::fake(function ($request) use ($delivery, $response, &$calls) {
        $calls++;
        expect($delivery->fresh()->provider_message_id)->toBeNull();
        app(LabsMobileAcknowledgementService::class)->record(labsAckEvidence($delivery));
        if ($response === 'timeout') {
            throw new \Illuminate\Http\Client\ConnectionException('synthetic-timeout');
        }

        return Http::response($response === 'accepted' ? ['code' => 0, 'subid' => $request['subid']] : [], 200);
    });
    $orchestrator = app(CommunicationOrchestrator::class);
    $orchestrator->process();
    expect($orchestrator->send($delivery->id))->toBeTrue();
    $this->travel(1)->seconds();
    expect($orchestrator->send($delivery->id))->toBeFalse();
    expect($delivery->fresh()->status)->toBe('delivered')->and($delivery->fresh()->read_at)->toBeNull()
        ->and($delivery->fresh()->provider_correlation_id)->toBe($delivery->provider_correlation_id)
        ->and($delivery->fresh()->provider_message_id)->toBe($response === 'accepted' ? $delivery->provider_correlation_id : null)
        ->and($delivery->fresh()->accepted_at !== null)->toBe($response === 'accepted');
    expect(Activity::where('event', 'communication.delivery_status_updated')->count())->toBe(1)
        ->and(Activity::where('event', 'communication.delivery_accepted')->count())->toBe($response === 'accepted' ? 1 : 0);
    expect($calls)->toBe(1);
})->with(['accepted', 'timeout', 'malformed']);

test('la recepción pública permanece cerrada y las capacidades no prometen seguimiento activo', function () {
    [, , , $delivery] = labsAckFixture($this);
    $payload = ['subid' => $delivery->provider_correlation_id, 'msisdn' => '525512345678', 'acklevel' => 'handset',
        'status' => 'ok', 'timestamp' => now()->utc()->format('Y-m-d H:i:s')];
    foreach (['/api/webhooks/comunicaciones/sms/'.$delivery->team_id, '/api/webhooks/comunicaciones/labsmobile'] as $url) {
        $this->getJson($url.'?'.http_build_query($payload))->assertNotFound();
        $this->postJson($url, $payload)->assertNotFound();
    }
    expect(app(LabsMobileSmsTransport::class)->capabilities()['delivery_receipts'])->toBeFalse()
        ->and(app(LabsMobileSmsTransport::class)->capabilities()['read_receipts'])->toBeFalse()
        ->and(config('comunicaciones.send_enabled'))->toBeFalse()->and(config('comunicaciones.sms_send_enabled'))->toBeFalse()
        ->and($delivery->fresh()->status)->toBe('sending');
    $this->assertDatabaseCount('communication_webhook_events', 0);
    Http::assertNothingSent();
});

test('la migración aditiva se revierte sin perder columnas y filas del contrato anterior', function () {
    [, , , $delivery] = labsAckFixture($this);
    DB::table('communication_webhook_events')->insert(['team_id' => $delivery->team_id, 'provider' => 'meta_cloud',
        'provider_message_id' => 'synthetic-meta', 'event_key' => 'synthetic-migration', 'status' => 'read', 'received_at' => now()]);
    $migration = require database_path('migrations/2026_11_25_180000_add_labsmobile_acknowledgements_to_webhook_events.php');
    try {
        $migration->down();
        expect(Schema::hasColumn('communication_webhook_events', 'ack_level'))->toBeFalse()
            ->and(DB::table('communication_webhook_events')->value('provider_message_id'))->toBe('synthetic-meta');
    } finally {
        $migration->up();
    }
    expect(Schema::hasColumn('communication_webhook_events', 'ack_level'))->toBeTrue()
        ->and(DB::table('communication_webhook_events')->value('ack_level'))->toBeNull();
});

test('el instante UTC del proveedor se convierte a la zona de almacenamiento de la aplicación', function () {
    [, , , $delivery] = labsAckFixture($this);
    $timezone = date_default_timezone_get();
    try {
        config(['app.timezone' => 'America/Mexico_City']);
        date_default_timezone_set('America/Mexico_City');
        $this->travelTo(now('America/Mexico_City'));
        $delivery->update(['attempted_at' => now()->subMinute()]);
        $ack = labsAckEvidence($delivery);
        expect(app(LabsMobileAcknowledgementService::class)->record($ack))->toBeTrue();
        expect($delivery->fresh()->getRawOriginal('delivered_at'))
            ->toBe($ack->occurredAt->setTimezone('America/Mexico_City')->format('Y-m-d H:i:s'));
        expect(DB::table('communication_webhook_events')->value('occurred_at'))
            ->toBe($ack->occurredAt->format('Y-m-d H:i:s'));
    } finally {
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }
});

test('un acuse posterior al timeout resuelve por correlación sin inventar aceptación HTTP', function () {
    [, , , $delivery] = labsAckFixture($this);
    $delivery->update(['status' => 'uncertain']);
    $service = app(LabsMobileAcknowledgementService::class);
    expect($service->record(labsAckEvidence($delivery)))->toBeTrue();
    $service->reconcile($delivery);
    expect($delivery->fresh()->status)->toBe('delivered')
        ->and($delivery->fresh()->provider_message_id)->toBeNull()->and($delivery->fresh()->accepted_at)->toBeNull()
        ->and($delivery->fresh()->read_at)->toBeNull();
    expect($service->record(labsAckEvidence($delivery)))->toBeFalse();
    Http::assertNothingSent();
});

test('un rechazo HTTP definitivo supera un acuse intermedio sin degradar una entrega', function () {
    [, , , $delivery] = labsAckFixture($this);
    $delivery->update(['status' => 'prepared', 'attempted_at' => null]);
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true]);
    Http::fake(function () use ($delivery) {
        app(LabsMobileAcknowledgementService::class)->record(labsAckEvidence($delivery, ['acklevel' => 'operator', 'desc' => null]));

        return Http::response(['code' => 35], 200);
    });
    $orchestrator = app(CommunicationOrchestrator::class);
    $orchestrator->process();
    $orchestrator->send($delivery->id);
    expect($delivery->fresh()->status)->toBe('failed')
        ->and($delivery->fresh()->delivered_at)->toBeNull()->and($delivery->fresh()->accepted_at)->toBeNull();
    expect(Activity::where('event', 'communication.delivery_failed')->count())->toBe(1);
    Http::assertSentCount(1);
});
