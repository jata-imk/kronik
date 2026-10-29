<?php

use App\Enums\ActivityEvent;
use App\Models\Permission;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('filtros y CSV permiten todas las sucursales del equipo aunque no estén asignadas al lector', function () {
    $auditor = actingAsActivityAuditor();
    $other = actingAsActivityAuditor();
    $branch = \App\Models\Sucursal::create(['clave' => 'ARCHIVO-LOG', 'nombre' => 'Archivo', 'activa' => false]);
    $second = \App\Models\Sucursal::create(['clave' => 'NORTE-LOG', 'nombre' => 'Norte', 'activa' => true]);
    $foreign = \App\Models\Sucursal::create(['clave' => 'AJENA-LOG', 'nombre' => 'Ajena', 'activa' => true]);
    foreach ([[$auditor, $branch, 'archivo visible'], [$auditor, $second, 'norte visible'], [$other, $foreign, 'evento ajeno']] as [$actor, $sucursal, $description]) {
        app(ActivityLogService::class)->log(ActivityEvent::Login, $description, causer: $actor,
            teamId: $actor->current_team_id, sucursalId: $sucursal->id, inheritActorContext: false);
    }
    expect($auditor->sucursales()->count())->toBe(0);
    $this->actingAs($auditor)->get(route('admin.users.activity'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.team_id', $auditor->current_team_id)
            ->where('filterOptions.allowAllTeams', false)->has('activityLogs.data', 2)
            ->has('filterOptions.sucursales', 2)->where('filterOptions.sucursales.0.id', $branch->id));
    $filters = ['team_id' => $auditor->current_team_id, 'sucursal_id' => $branch->id];
    $this->get(route('admin.users.activity', $filters))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('activityLogs.data', 1)
            ->where('activityLogs.data.0.description', 'archivo visible')->where('filters.sucursal_id', $branch->id));
    $csv = $this->get(route('admin.users.activity.export', $filters))->assertOk()->streamedContent();
    expect($csv)->toContain('archivo visible')->not->toContain('norte visible', 'evento ajeno');
    $this->get(route('admin.users.activity', ['sucursal_id' => $foreign->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('activityLogs.total', 0));
    $this->get(route('admin.users.activity.export', ['team_id' => $other->current_team_id, 'sucursal_id' => $branch->id]))->assertForbidden();
});

test('el selector solo ofrece equipos con pertenencia y permiso propio sin alterar preferencias ni navegación', function () {
    $auditor = actingAsActivityAuditor();
    $current = $auditor->current_team_id;
    $selected = \App\Models\Team::factory()->create(['name' => 'Equipo autorizado']);
    $selected->users()->attach($auditor);
    grantTeamPermissions($auditor, $selected, ['read activity-log']);
    $withoutPermission = \App\Models\Team::factory()->create(['name' => 'Equipo sin permiso']);
    $withoutPermission->users()->attach($auditor);
    $outside = \App\Models\Team::factory()->create();
    $this->actingAs($auditor)->get(route('admin.users.activity', ['team_id' => $selected->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('activityTeam.id', $selected->id)
            ->has('filterOptions.teams', 2)
            ->where('filterOptions.teams', fn ($teams) => collect($teams)->pluck('id')->sort()->values()->all() === collect([$current, $selected->id])->sort()->values()->all())
            ->where('auth.user.current_team_id', $current));
    expect($auditor->fresh()->current_team_id)->toBe($current)->and(getPermissionsTeamId())->toBe($current);
    $this->get(route('admin.users.activity', ['team_id' => $withoutPermission->id]))->assertForbidden();
    $this->get(route('admin.users.activity', ['team_id' => $outside->id]))->assertForbidden();
});

test('opciones dependientes y paginación respetan equipo y sucursal seleccionados', function () {
    $admin = actingAsSuperAdmin();
    $actor = User::factory()->withPersonalTeam()->create();
    $actor->switchTeam($actor->ownedTeams()->firstOrFail());
    $branch = \App\Models\Sucursal::create(['clave' => 'LOG-A', 'nombre' => 'Sucursal A', 'activa' => true]);
    $otherBranch = \App\Models\Sucursal::create(['clave' => 'LOG-B', 'nombre' => 'Sucursal B', 'activa' => true]);
    for ($i = 0; $i < 11; $i++) {
        app(ActivityLogService::class)->log(ActivityEvent::Login, 'Evento paginado '.$i, causer: $actor,
            teamId: $actor->current_team_id, sucursalId: $branch->id, inheritActorContext: false);
    }
    activity()->event('solo.otra.sucursal')->tap(function ($activity) use ($actor, $otherBranch) {
        $activity->team_id = $actor->current_team_id;
        $activity->sucursal_id = $otherBranch->id;
    })->causedBy($admin)->log('Otro usuario y evento');
    $filters = ['team_id' => $actor->current_team_id, 'sucursal_id' => $branch->id, 'per_page' => 10];
    $this->actingAs($admin)->get(route('admin.users.activity', $filters))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('activityLogs.data', 10)->where('activityLogs.total', 11)
            ->has('filterOptions.users', 1)->where('filterOptions.users.0.id', $actor->id)
            ->where('filterOptions.events', fn ($events) => ! collect($events)->contains('value', 'solo.otra.sucursal'))
            ->where('activityLogs.next_page_url', fn ($url) => str_contains($url, 'team_id='.$actor->current_team_id) && str_contains($url, 'sucursal_id='.$branch->id)));
    $this->get(route('admin.users.activity', [...$filters, 'page' => 2]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('activityLogs.data', 1)->where('filterOptions.allowAllTeams', true));
    expect($admin->fresh()->current_team_id)->not->toBe($actor->current_team_id);
});

test('filtros de actividad rechazan datos inválidos con mensajes españoles', function (string $field, mixed $value, string $attribute) {
    $admin = actingAsSuperAdmin();
    foreach (['admin.users.activity', 'admin.users.activity.export'] as $route) {
        $response = $this->actingAs($admin)->getJson(route($route, [$field => $value]));
        $response->assertUnprocessable()->assertJsonValidationErrors($field);
        expect($response->json('errors.'.$field.'.0'))->toContain($attribute)->not->toContain('validation.');
    }
})->with([
    ['team_id', 'incorrecto', 'equipo seleccionado'], ['sucursal_id', 'incorrecta', 'sucursal seleccionada'],
    ['sucursal_id', 999999, 'sucursal seleccionada'], ['per_page', 13, 'registros por página'],
    ['date_to', 'incorrecta', 'fecha final'],
]);

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
            ->where('activityLogs.data.0.team.id', $user->current_team_id)
            ->where('activityLogs.data.0.sucursal.id', $user->current_sucursal_id)
        );

    $export = $this->actingAs($user)
        ->get(route('admin.users.activity.export', ['event' => 'login']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($export->streamedContent())
        ->toContain('Inicio de sesion exitoso')
        ->toContain($user->currentTeam->name)
        ->toContain($user->currentSucursal->nombre);
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
