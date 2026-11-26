<?php

use App\Models\CommunicationChannel;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

test('el esquema acumulado SMS y Notificaciones admite rollback y reinstalación sin perder correo o Meta', function () {
    $originalConnection = DB::getDefaultConnection();
    $originalSchema = Schema::getFacadeRoot();
    config(['database.connections.communication_schema' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
    DB::setDefaultConnection('communication_schema');
    Schema::swap(DB::connection()->getSchemaBuilder());
    try {
        expect(Artisan::call('migrate', ['--database' => 'communication_schema', '--force' => true]))->toBe(0);
        $actor = User::factory()->withPersonalTeam()->create();
        $team = $actor->ownedTeams()->sole();
        foreach (['email' => 'smtp', 'whatsapp' => 'meta_cloud'] as $channel => $provider) {
            CommunicationChannel::create(['team_id' => $team->id, 'channel' => $channel, 'provider' => $provider,
                'enabled' => false, 'sender' => 'synthetic', 'eligibility' => 'testing']);
        }
        DB::table('communication_webhook_events')->insert(['team_id' => $team->id, 'provider' => 'meta_cloud',
            'provider_message_id' => 'synthetic-schema', 'event_key' => 'schema-meta', 'status' => 'delivered', 'received_at' => now()]);
        $files = [
            '2026_11_25_170000_add_sms_provider_correlation_to_communication_deliveries.php',
            '2026_11_25_180000_add_labsmobile_acknowledgements_to_webhook_events.php',
            '2026_11_26_100000_create_notification_templates.php',
        ];
        foreach (array_reverse($files) as $file) {
            (require database_path('migrations/'.$file))->down();
        }
        expect(Schema::hasTable('notification_templates'))->toBeFalse()
            ->and(Schema::hasColumn('communication_deliveries', 'provider_correlation_id'))->toBeFalse()
            ->and(Schema::hasColumn('communication_webhook_events', 'ack_level'))->toBeFalse()
            ->and(CommunicationChannel::count())->toBe(2)
            ->and(DB::table('communication_webhook_events')->where('event_key', 'schema-meta')->value('provider'))->toBe('meta_cloud');
        $modules = DB::table('modules')->count();
        $permissions = DB::table('permissions')->count();
        foreach ($files as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        expect(Schema::hasTable('notification_template_versions'))->toBeTrue()
            ->and(Schema::hasColumn('communication_deliveries', 'message_snapshot'))->toBeTrue()
            ->and(Schema::hasColumn('communication_deliveries', 'provider_correlation_id'))->toBeTrue()
            ->and(Schema::hasColumn('communication_webhook_events', 'ack_level'))->toBeTrue()
            ->and(CommunicationChannel::count())->toBe(2)
            ->and(DB::table('communication_webhook_events')->where('event_key', 'schema-meta')->value('status'))->toBe('delivered')
            ->and(DB::table('modules')->count())->toBe($modules)
            ->and(DB::table('permissions')->count())->toBe($permissions);
    } finally {
        DB::setDefaultConnection($originalConnection);
        Schema::swap($originalSchema);
        DB::purge('communication_schema');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
});
