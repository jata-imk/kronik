<?php

use App\Jobs\SendCommunicationDelivery;
use App\Models\Activity;
use App\Models\CommunicationChannel;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\CommunicationRule;
use App\Services\ActivityLogService;
use App\Services\Communications\CommunicationOrchestrator;
use App\Services\Communications\SmsTestDataCleanup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    config(['comunicaciones.send_enabled' => false, 'comunicaciones.sms_send_enabled' => false,
        'queue.connections.database.connection' => null, 'queue.failed.database' => DB::getDefaultConnection()]);
    Http::preventStrayRequests();
    Http::fake();
});

function smsCleanupFixture($test): array
{
    [$actor, $credit] = creditoPagableQa($test);
    $event = CommunicationEvent::create(['team_id' => $credit->team_id, 'sucursal_id' => $credit->sucursal_id,
        'cliente_id' => $credit->cliente_id, 'credito_id' => $credit->id, 'event_type' => 'payment_received', 'event_key' => 'synthetic-cleanup']);
    $deliveries = [];
    foreach (['sms' => 'infobip', 'email' => 'smtp', 'whatsapp' => 'meta_cloud'] as $name => $provider) {
        $channel = CommunicationChannel::create(['team_id' => $credit->team_id, 'channel' => $name, 'provider' => $provider, 'enabled' => true]);
        DB::table('communication_channels')->where('id', $channel->id)->update(['secret' => 'UNREADABLE-SYNTHETIC-SECRET']);
        CommunicationRule::create(['team_id' => $credit->team_id, 'event_type' => 'payment_received', 'channel' => $name,
            'body' => 'Synthetic', 'enabled' => true]);
        $delivery = CommunicationDelivery::create(['team_id' => $credit->team_id, 'communication_event_id' => $event->id,
            'cliente_id' => $credit->cliente_id, 'credito_id' => $credit->id, 'channel' => $name, 'status' => 'queued',
            'recipient_hash' => str_repeat('a', 64), 'template_version' => 1]);
        $deliveries[$name] = $delivery;
        $class = SendCommunicationDelivery::class;
        $payload = json_encode(['displayName' => $class, 'data' => ['commandName' => $class,
            'command' => serialize(new SendCommunicationDelivery($delivery->id))]], JSON_THROW_ON_ERROR);
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => $payload, 'attempts' => 0,
            'reserved_at' => null, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp]);
        DB::table('communication_webhook_events')->insert(['team_id' => $credit->team_id, 'communication_delivery_id' => $delivery->id,
            'provider' => $provider, 'provider_message_id' => 'synthetic-'.$name, 'event_key' => 'cleanup-'.$name,
            'status' => 'accepted', 'received_at' => now()]);
    }
    $smsPayload = DB::table('jobs')->orderBy('id')->value('payload');
    DB::table('failed_jobs')->insert(['uuid' => 'synthetic-sms-uuid', 'connection' => 'database', 'queue' => 'default',
        'payload' => $smsPayload, 'exception' => 'synthetic-error', 'failed_at' => now()]);
    DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{"displayName":"unknown"}',
        'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp]);

    return [$actor, $credit, $event, $deliveries];
}

test('diagnóstico no escribe y limpieza selectiva conserva correo Meta eventos y jobs compartidos', function () {
    [, $credit, $event, $deliveries] = smsCleanupFixture($this);
    $service = app(SmsTestDataCleanup::class);
    $count = Activity::count();
    $report = $service->run();
    expect($report['channels'])->toBe(1)->and($report['jobs'])->toBe(2)->and($report['unclassified_jobs'])->toBe(1)
        ->and(Activity::count())->toBe($count);
    expect($deliveries['sms']->fresh()->status)->toBe('queued');
    $this->artisan('comunicaciones:limpiar-sms-prueba', ['--apply' => true, '--confirm-qa' => true])->assertSuccessful();
    expect(CommunicationChannel::where('channel', 'sms')->exists())->toBeFalse()
        ->and(CommunicationChannel::where('channel', 'email')->sole()->getRawOriginal('secret'))->toBe('UNREADABLE-SYNTHETIC-SECRET')
        ->and(CommunicationChannel::where('channel', 'whatsapp')->sole()->getRawOriginal('secret'))->toBe('UNREADABLE-SYNTHETIC-SECRET')
        ->and($deliveries['sms']->fresh()->status)->toBe('cancelled')
        ->and($deliveries['email']->fresh()->status)->toBe('queued')
        ->and($deliveries['whatsapp']->fresh()->status)->toBe('queued')
        ->and(CommunicationEvent::find($event->id))->not->toBeNull();
    $this->assertDatabaseCount('jobs', 3);
    $this->assertDatabaseCount('failed_jobs', 0);
    $this->assertDatabaseCount('communication_webhook_events', 2);
    expect(CommunicationRule::where('channel', 'sms')->sole()->enabled)->toBeFalse()
        ->and(CommunicationRule::where('channel', 'email')->sole()->enabled)->toBeTrue();
    $audits = Activity::where('event', 'communication.sms_test_data_cleaned')->get();
    expect($audits)->toHaveCount(4);
    foreach ($audits as $audit) {
        expect($audit->causer_id)->toBeNull()->and($audit->team_id)->toBe($credit->team_id)
            ->and($audit->properties->toJson())->not->toContain('UNREADABLE-SYNTHETIC-SECRET', 'payload', 'synthetic-sms-uuid');
    }
    $count = Activity::count();
    CommunicationChannel::create(['team_id' => $credit->team_id, 'channel' => 'sms', 'provider' => 'labsmobile']);
    CommunicationRule::where('channel', 'sms')->update(['enabled' => true]);
    expect($service->run(true)['jobs'])->toBe(0)->and(Activity::count())->toBe($count);
    expect(CommunicationRule::where('channel', 'sms')->sole()->enabled)->toBeTrue();
    // Even unidentified stale jobs cannot reinterpret a cancelled delivery as a LabsMobile attempt.
    config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true]);
    (new SendCommunicationDelivery($deliveries['sms']->id))->handle(app(CommunicationOrchestrator::class));
    Http::assertNothingSent();
});

test('la limpieza exige declaración QA y barreras cerradas', function () {
    $this->artisan('comunicaciones:limpiar-sms-prueba', ['--apply' => true])->assertFailed();
    config(['comunicaciones.send_enabled' => true]);
    $this->artisan('comunicaciones:limpiar-sms-prueba', ['--apply' => true, '--confirm-qa' => true])->assertFailed();
    config(['comunicaciones.send_enabled' => false, 'comunicaciones.sms_send_enabled' => true]);
    $this->artisan('comunicaciones:limpiar-sms-prueba', ['--apply' => true, '--confirm-qa' => true])->assertFailed();
    $this->app->instance('env', 'qa');
    config(['comunicaciones.sms_send_enabled' => false]);
    $this->artisan('comunicaciones:limpiar-sms-prueba', ['--apply' => true, '--confirm-qa' => true])->assertFailed();
});

test('un fallo de auditoría revierte configuración salidas y eliminación de jobs', function () {
    [, , , $deliveries] = smsCleanupFixture($this);
    $this->mock(ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('synthetic-failure'));
    try {
        app(SmsTestDataCleanup::class)->run(true);
    } catch (RuntimeException) {
        expect($deliveries['sms']->fresh()->status)->toBe('queued');
        $this->assertDatabaseCount('jobs', 4);
        $this->assertDatabaseCount('failed_jobs', 1);
        $this->assertDatabaseCount('communication_channels', 3);

        return;
    }
    $this->fail('Se esperaba rollback por fallo de auditoría.');
});

test('identificación ambigua y almacenes externos se conservan sin deserializar ni conectar', function () {
    [, , , $deliveries] = smsCleanupFixture($this);
    $job = DB::table('jobs')->orderBy('id')->first();
    $payload = json_decode($job->payload, true);
    $payload['displayName'] = 'unrelated-job';
    DB::table('jobs')->where('id', $job->id)->update(['payload' => json_encode($payload)]);
    config(['queue.connections.external' => ['driver' => 'database', 'connection' => 'unavailable', 'table' => 'jobs']]);
    $report = app(SmsTestDataCleanup::class)->run(true);
    expect($report['jobs'])->toBe(1)->and($report['unclassified_jobs'])->toBe(2)->and($report['uninspected_stores'])->toBeGreaterThan(0)
        ->and(DB::table('jobs')->where('id', $job->id)->exists())->toBeTrue()
        ->and($deliveries['sms']->fresh()->status)->toBe('cancelled');
});
