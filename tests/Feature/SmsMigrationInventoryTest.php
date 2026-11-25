<?php

use App\Jobs\SendCommunicationDelivery;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\User;
use App\Services\Communications\SmsMigrationInventory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    Http::preventStrayRequests();
    Mail::fake();
    Queue::fake();
    config([
        'queue.default' => 'database',
        'queue.connections.database.connection' => null,
        'queue.connections.database.table' => 'jobs',
        'queue.failed.driver' => 'database-uuids',
        'queue.failed.database' => config('database.default'),
        'queue.failed.table' => 'failed_jobs',
        'comunicaciones.send_enabled' => false,
    ]);
});

function inventoryJobPayload(string $name = SendCommunicationDelivery::class): string
{
    return json_encode(['displayName' => $name, 'data' => ['commandName' => $name,
        'command' => 'not-a-serialized-object:SECRET-PAYLOAD']], JSON_THROW_ON_ERROR);
}

test('el inventario vacío es repetible y solo ejecuta lecturas sin enviar ni auditar', function () {
    $this->freezeTime();
    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });
    $service = app(SmsMigrationInventory::class);
    $first = $service->collect();
    expect($service->collect())->toBe($first)
        ->and($first['complete'])->toBeTrue()
        ->and($first['cutover_ready'])->toBeFalse()
        ->and($first['channels']['total'])->toBe(0)
        ->and($first['deliveries']['total'])->toBe(0)
        ->and($first['deliveries']['provider_provenance'])->toBe('not_recorded')
        ->and($first['webhooks']['total'])->toBe(0)
        ->and($first['failed_jobs']['communication'])->toBe(0);
    foreach ($queries as $sql) {
        expect(preg_match('/^\s*(select|pragma)\b/i', $sql))->toBe(1);
    }
    expect(DB::table('activity_log')->count())->toBe(0);
    Http::assertNothingSent();
    Queue::assertNothingPushed();
    Mail::assertNothingSent();
});

test('los estados y la procedencia se cuentan sin descifrar secretos ni imprimir identificadores', function () {
    [$actor, $credit] = creditoPagableQa($this);
    DB::table('communication_channels')->insert([
        'team_id' => $credit->team_id, 'channel' => 'sms', 'provider' => 'infobip',
        'secret' => 'INVALID-CIPHERTEXT-SECRET', 'sender' => '+525599999999',
        'enabled' => true, 'eligibility' => 'approved',
    ]);
    foreach (['prepared', 'accepted', 'uncertain'] as $index => $state) {
        $event = CommunicationEvent::create(['event_key' => 'inventory:'.$index, 'event_type' => 'payment_received',
            'team_id' => $credit->team_id, 'credito_id' => $credit->id, 'cliente_id' => $credit->cliente_id,
            'sucursal_id' => $credit->sucursal_id]);
        CommunicationDelivery::create(['team_id' => $credit->team_id, 'communication_event_id' => $event->id,
            'cliente_id' => $credit->cliente_id, 'credito_id' => $credit->id, 'channel' => 'sms',
            'recipient_hash' => 'PRIVATE-RECIPIENT-HASH', 'template_version' => 1, 'status' => $state,
            'provider_message_id' => $index ? 'PRIVATE-PROVIDER-ID-'.$index : null]);
    }
    DB::table('communication_webhook_events')->insert([
        'team_id' => $credit->team_id, 'provider' => 'infobip', 'provider_message_id' => 'PRIVATE-CALLBACK-ID',
        'event_key' => 'private-key', 'status' => 'delivered', 'received_at' => now(),
    ]);
    $before = DB::table('activity_log')->count();
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['channels']['by_provider']['infobip'])->toBe(1)
        ->and($report['channels']['with_stored_secret'])->toBe(1)
        ->and($report['channels']['enabled'])->toBe(1)
        ->and($report['deliveries']['total'])->toBe(3)
        ->and($report['deliveries']['with_external_id'])->toBe(2)
        ->and($report['deliveries']['by_status']['uncertain'])->toBe(1)
        ->and($report['deliveries']['provider_provenance'])->toBe('not_recorded')
        ->and($report['webhooks']['unmatched'])->toBe(1)
        ->and(DB::table('activity_log')->count())->toBe($before);
    $output = json_encode($report);
    foreach (['INVALID-CIPHERTEXT-SECRET', '+525599999999', 'PRIVATE-RECIPIENT-HASH', 'PRIVATE-PROVIDER-ID', 'PRIVATE-CALLBACK-ID'] as $private) {
        expect($output)->not->toContain($private);
    }
    $this->artisan('comunicaciones:inventariar-sms')->assertSuccessful();
    Http::assertNothingSent();
});

test('cuenta trabajos listos retrasados reservados y fallidos sin deserializar su contenido', function () {
    foreach ([[null, now()->timestamp], [null, now()->addHour()->timestamp], [now()->timestamp, now()->timestamp]] as [$reserved, $available]) {
        DB::table('jobs')->insert(['queue' => 'private-queue', 'payload' => inventoryJobPayload(), 'attempts' => 0,
            'reserved_at' => $reserved, 'available_at' => $available, 'created_at' => now()->timestamp]);
    }
    DB::table('jobs')->insert(['queue' => 'private-queue', 'payload' => inventoryJobPayload('App\\Jobs\\OtherJob'),
        'attempts' => 0, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp]);
    DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'private-queue',
        'payload' => inventoryJobPayload(), 'exception' => 'PRIVATE-EXCEPTION-TOKEN', 'failed_at' => now()]);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['queue']['scope'])->toBe('communication_jobs_all_channels')
        ->and($report['queue']['database_stores'][0])->toBe([
            'status' => 'available', 'ready' => 1, 'delayed' => 1, 'reserved' => 1, 'unrelated' => 1, 'unclassified' => 0, 'active' => true,
        ])
        ->and($report['failed_jobs']['communication'])->toBe(1)
        ->and(DB::table('jobs')->count())->toBe(4)
        ->and(DB::table('failed_jobs')->count())->toBe(1)
        ->and(json_encode($report))->not->toContain('SECRET-PAYLOAD')->not->toContain('PRIVATE-EXCEPTION')->not->toContain('private-queue');
});

test('sync sigue inspeccionando el almacén database que pudo contener trabajos anteriores', function () {
    config(['queue.default' => 'sync']);
    DB::table('jobs')->insert(['queue' => 'default', 'payload' => inventoryJobPayload(), 'attempts' => 0,
        'available_at' => now()->timestamp, 'created_at' => now()->timestamp]);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['queue']['active_driver'])->toBe('sync')
        ->and($report['queue']['database_stores'][0]['ready'])->toBe(1);
});

test('Redis no se presenta como cola vacía aunque el almacén database esté vacío', function () {
    config(['queue.default' => 'redis']);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['complete'])->toBeFalse()
        ->and($report['queue']['active_inspectable'])->toBeFalse()
        ->and($report['queue']['active_driver'])->toBe('redis');
    $this->artisan('comunicaciones:inventariar-sms', ['--json' => true])->assertFailed();
});

test('almacenes ausentes o deshabilitados no se presentan como conteos cero', function () {
    config(['queue.connections.database.table' => 'missing_inventory_jobs', 'queue.failed.driver' => null]);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['complete'])->toBeFalse()
        ->and($report['queue']['database_stores'][0])->toBe(['status' => 'unavailable', 'reason' => 'missing_table', 'active' => true])
        ->and($report['failed_jobs'])->toBe(['status' => 'unavailable', 'reason' => 'unsupported_or_disabled_storage']);
});

test('un error de conexión se informa sin exponer la excepción ni configuración privada', function () {
    config(['queue.connections.database.connection' => 'PRIVATE-NONEXISTENT-CONNECTION']);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['complete'])->toBeFalse()
        ->and($report['queue']['database_stores'][0])->toBe(['status' => 'unavailable', 'reason' => 'read_failed', 'active' => true])
        ->and(json_encode($report))->not->toContain('PRIVATE-NONEXISTENT-CONNECTION');
});

test('payload inválido queda sin clasificar y deja incompleta la observación', function () {
    DB::table('jobs')->insert(['queue' => 'default', 'payload' => 'BROKEN-PAYLOAD-SECRET', 'attempts' => 0,
        'available_at' => now()->timestamp, 'created_at' => now()->timestamp]);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['queue']['database_stores'][0]['unclassified'])->toBe(1)
        ->and($report['complete'])->toBeFalse()
        ->and(json_encode($report))->not->toContain('BROKEN-PAYLOAD-SECRET');
});

test('proveedores desconocidos se agrupan sin publicar valores libres y la barrera no certifica el corte', function () {
    $user = User::factory()->withPersonalTeam()->create();
    DB::table('communication_channels')->insert(['team_id' => $user->ownedTeams()->first()->id,
        'channel' => 'sms', 'provider' => 'PRIVATE-UNKNOWN-VALUE', 'enabled' => false, 'eligibility' => 'unverified']);
    config(['comunicaciones.send_enabled' => true]);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['channels']['by_provider']['other'])->toBe(1)
        ->and($report['external_sending_enabled'])->toBeTrue()
        ->and($report['cutover_ready'])->toBeFalse()
        ->and(json_encode($report))->not->toContain('PRIVATE-UNKNOWN-VALUE');
});

test('JSON de consola es procesable sin cabecera y conserva el código de observación completa', function () {
    expect(Artisan::call('comunicaciones:inventariar-sms', ['--json' => true]))->toBe(0);
    $report = json_decode(Artisan::output(), true, 32, JSON_THROW_ON_ERROR);
    expect($report['schema_version'])->toBe(1)
        ->and($report['read_only'])->toBeTrue()
        ->and($report['complete'])->toBeTrue();
});

test('tablas de comunicaciones ausentes producen secciones no disponibles', function () {
    Schema::rename('communication_channels', 'inventory_saved_channels');
    try {
        $report = app(SmsMigrationInventory::class)->collect();
        expect($report['channels'])->toBe(['status' => 'unavailable', 'reason' => 'missing_table'])
            ->and($report['complete'])->toBeFalse();
    } finally {
        Schema::rename('inventory_saved_channels', 'communication_channels');
    }
});

test('nombres contradictorios no permiten descartar un posible trabajo de comunicaciones', function () {
    $payload = json_encode(['displayName' => SendCommunicationDelivery::class,
        'data' => ['commandName' => 'App\\Jobs\\DifferentJob']], JSON_THROW_ON_ERROR);
    DB::table('jobs')->insert(['queue' => 'default', 'payload' => $payload, 'attempts' => 0,
        'available_at' => now()->timestamp, 'created_at' => now()->timestamp]);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['queue']['database_stores'][0]['unclassified'])->toBe(1)
        ->and($report['queue']['database_stores'][0]['unrelated'])->toBe(0)
        ->and($report['complete'])->toBeFalse();
});

test('respeta tablas y conexiones separadas de jobs y fallidos sin usar el almacén predeterminado', function () {
    config(['database.connections.inventory_queue' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]]);
    $connection = DB::connection('inventory_queue');
    try {
        $connection->getSchemaBuilder()->create('inventory_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('payload');
            $table->integer('available_at');
            $table->integer('reserved_at')->nullable();
        });
        $connection->getSchemaBuilder()->create('inventory_failed', function (Blueprint $table) {
            $table->id();
            $table->text('payload');
        });
        $connection->table('inventory_jobs')->insert(['payload' => inventoryJobPayload(), 'available_at' => now()->timestamp]);
        $connection->table('inventory_failed')->insert(['payload' => inventoryJobPayload()]);
        config(['queue.connections.database.connection' => 'inventory_queue', 'queue.connections.database.table' => 'inventory_jobs',
            'queue.failed.database' => 'inventory_queue', 'queue.failed.table' => 'inventory_failed']);
        $report = app(SmsMigrationInventory::class)->collect();
        expect($report['complete'])->toBeTrue()
            ->and($report['queue']['database_stores'][0]['ready'])->toBe(1)
            ->and($report['failed_jobs']['communication'])->toBe(1)
            ->and(DB::table('jobs')->count())->toBe(0);
    } finally {
        DB::purge('inventory_queue');
    }
});

test('la conexión database activa se identifica aunque haya otros almacenes configurados', function () {
    config(['queue.default' => 'secondary',
        'queue.connections.secondary' => ['driver' => 'database', 'connection' => null, 'table' => 'missing_secondary_jobs']]);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['queue']['database_stores'][0]['active'])->toBeFalse()
        ->and($report['queue']['database_stores'][0]['status'])->toBe('available')
        ->and($report['queue']['database_stores'][1]['active'])->toBeTrue()
        ->and($report['queue']['active_inspectable'])->toBeFalse()
        ->and($report['complete'])->toBeFalse();
    config(['queue.default' => 'database']);
    $report = app(SmsMigrationInventory::class)->collect();
    expect($report['queue']['active_inspectable'])->toBeTrue()
        ->and($report['complete'])->toBeFalse();
});
