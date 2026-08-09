<?php

use App\Enums\ActivityEvent;
use App\Models\Permission;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function actingAsActivityAuditor(): User
{
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->ownedTeams()->firstOrFail();
    $user->forceFill(['current_team_id' => $team->id])->save();

    setPermissionsTeamId($team->id);

    $permission = Permission::firstOrCreate([
        'name' => 'read activity-log',
        'guard_name' => 'web',
    ]);
    $roleModel = config('permission.models.role');
    $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
    $role = $roleModel::firstOrCreate([
        'name' => 'Activity auditor',
        'guard_name' => 'web',
        $teamKey => $team->id,
    ]);

    $role->givePermissionTo($permission);
    $user->assignRole($role);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->refresh();
}

test('super admin can inspect and export real activity logs', function () {
    $user = actingAsSuperAdmin();

    app(ActivityLogService::class)->log(
        event: ActivityEvent::Login,
        description: 'Inicio de sesion exitoso',
        causer: $user,
    );

    $this->actingAs($user)
        ->get(route('admin.users.activity', ['event' => 'login']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Logs/UserActivity')
            ->where('activityLogs.data.0.event', 'login')
            ->where('activityLogs.data.0.causer.id', $user->id)
            ->where('activityLogs.data.0.ip', '127.0.0.1')
        );

    $export = $this->actingAs($user)
        ->get(route('admin.users.activity.export', ['event' => 'login']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($export->streamedContent())->toContain('Inicio de sesion exitoso');
});

test('activity logs, filter options, and exports are limited to the current team', function () {
    $auditor = actingAsActivityAuditor();
    $otherAuditor = actingAsActivityAuditor();
    $logger = app(ActivityLogService::class);

    $logger->log(
        event: ActivityEvent::BranchCreated,
        description: 'Evento del equipo actual',
        causer: $auditor,
    );
    $logger->log(
        event: ActivityEvent::BranchCreated,
        description: 'Evento de otro equipo',
        causer: $otherAuditor,
    );
    DB::table(config('activitylog.table_name'))->insert([
        'log_name' => 'default',
        'description' => 'Evento histórico sin equipo',
        'causer_type' => User::class,
        'causer_id' => $auditor->id,
        'event' => 'legacy',
        'properties' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas(config('activitylog.table_name'), [
        'description' => 'Evento del equipo actual',
        'team_id' => $auditor->current_team_id,
    ]);

    $this->actingAs($auditor)
        ->get(route('admin.users.activity'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activityLogs.total', 1)
            ->where('activityLogs.data.0.description', 'Evento del equipo actual')
            ->where('filterOptions.users.0.id', $auditor->id)
        );

    $export = $this->actingAs($auditor)
        ->get(route('admin.users.activity.export'))
        ->assertOk();

    expect($export->streamedContent())
        ->toContain('Evento del equipo actual')
        ->not->toContain('Evento de otro equipo')
        ->not->toContain('Evento histórico sin equipo');
});

test('super admins can inspect activity across teams, including historical records', function () {
    $superAdmin = actingAsSuperAdmin();
    $auditor = actingAsActivityAuditor();
    $logger = app(ActivityLogService::class);

    $logger->log(
        event: ActivityEvent::BranchCreated,
        description: 'Evento de otro equipo',
        causer: $auditor,
    );
    DB::table(config('activitylog.table_name'))->insert([
        'log_name' => 'default',
        'description' => 'Evento histórico sin equipo',
        'causer_type' => User::class,
        'causer_id' => $superAdmin->id,
        'event' => null,
        'properties' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.users.activity'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activityLogs.total', 2)
            ->where('filterOptions.events', fn ($events) => collect($events)
                ->contains('value', 'legacy.unclassified'))
        );

    $this->actingAs($superAdmin)
        ->get(route('admin.users.activity', ['event' => 'legacy.unclassified']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activityLogs.total', 1)
            ->where('activityLogs.data.0.event', 'legacy.unclassified')
            ->where('activityLogs.data.0.event_label', 'Sin clasificar (histórico)')
        );
});

test('activity CSV values that look like formulas are exported as text', function () {
    $auditor = actingAsActivityAuditor();

    app(ActivityLogService::class)->log(
        event: ActivityEvent::BranchUpdated,
        description: '=HYPERLINK("https://example.test","abrir")',
        causer: $auditor,
    );

    $export = $this->actingAs($auditor)
        ->get(route('admin.users.activity.export'))
        ->assertOk();

    expect($export->streamedContent())->toContain("'=HYPERLINK");
});

test('activity metadata keeps field names and rejects sensitive values', function () {
    $auditor = actingAsActivityAuditor();

    $activity = app(ActivityLogService::class)->log(
        event: ActivityEvent::ClientUpdated,
        description: 'Cliente actualizado',
        metadata: [
            'changed_fields' => ['email', 'datos_fiscales.rfc'],
            'related' => ['type' => 'cliente', 'id' => 25, 'name' => 'Dato privado'],
            'result' => 'success',
            'rfc' => 'XAXX010101000',
            'email' => 'privado@example.test',
        ],
        causer: $auditor,
    );

    expect($activity->properties->toArray())
        ->toHaveKey('changed_fields', ['email', 'datos_fiscales.rfc'])
        ->toHaveKey('related', ['type' => 'cliente', 'id' => 25])
        ->toHaveKey('result', 'success')
        ->not->toHaveKeys(['rfc', 'email']);
});
