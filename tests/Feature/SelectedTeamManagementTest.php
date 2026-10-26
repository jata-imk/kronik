<?php

use App\Enums\UserStatus;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamContext;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('selected team permissions govern role operations without changing current team or navigation', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $current = $user->currentTeam;
    $selected = Team::factory()->create();
    $selected->users()->attach($user);
    grantTeamPermissions($user, $selected, ['read roles', 'create roles', 'update roles', 'delete roles']);
    $role = Role::query()->create(['name' => 'Rol seleccionado', 'guard_name' => 'web', 'team_id' => $selected->id]);
    $this->actingAs($user)->get(route('admin.roles.index', ['team' => $selected->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('roleTeam.id', $selected->id)->where('roleCapabilities.update', true)
            ->where('auth.permissions.read-roles', false)->where('roles.0.id', $role->id));
    $this->post(route('admin.roles.store', ['team' => $selected->id]), ['name' => 'Nuevo', 'team_id' => $current->id])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('roles', ['name' => 'Nuevo', 'team_id' => $selected->id]);
    $this->put(route('admin.roles.update', ['role' => $role, 'team' => $selected->id]), ['name' => 'Renombrado', 'permissions' => []])->assertSessionHasNoErrors();
    $this->delete(route('admin.roles.destroy', ['role' => $role, 'team' => $selected->id]))->assertSessionHas('success', 'Rol eliminado');
    expect($user->fresh()->current_team_id)->toBe($current->id)->and(getPermissionsTeamId())->toBe($current->id);
    $this->get(route('admin.roles.index'))->assertForbidden();
});

test('selected team rejects outsiders and current-team authority cannot authorize another team', function () {
    $user = User::factory()->withPersonalTeam()->create();
    grantTeamPermissions($user, $user->currentTeam, ['read roles', 'create roles']);
    $selected = Team::factory()->create();
    $this->actingAs($user)->get(route('admin.roles.index', ['team' => $selected->id]))->assertForbidden();
    $selected->users()->attach($user);
    $user->unsetRelation('teams');
    $this->get(route('admin.roles.index', ['team' => $selected->id]))->assertForbidden();
    $this->post(route('admin.roles.store', ['team' => $selected->id]), ['name' => 'Prohibido'])->assertForbidden();
});

test('selected role writes still reject other roles and count assigned users in the selected team', function () {
    $admin = actingAsSuperAdmin();
    $selected = Team::factory()->create();
    $ownRole = Role::query()->create(['name' => 'Actual', 'guard_name' => 'web', 'team_id' => $admin->current_team_id]);
    $selectedRole = Role::query()->create(['name' => 'Asignado', 'guard_name' => 'web', 'team_id' => $selected->id]);
    $member = User::factory()->create();
    DB::table('model_has_roles')->insert(['role_id' => $selectedRole->id, 'model_id' => $member->id, 'model_type' => $member->getMorphClass(), 'team_id' => $selected->id]);
    $this->actingAs($admin)->put(route('admin.roles.update', ['role' => $ownRole, 'team' => $selected->id]), ['name' => 'Ajeno'])->assertNotFound();
    $this->delete(route('admin.roles.destroy', ['role' => $selectedRole, 'team' => $selected->id]))->assertSessionHasErrors('message');
    $apiRole = Role::query()->create(['name' => 'API externa', 'guard_name' => 'api', 'team_id' => $selected->id]);
    $this->put(route('admin.roles.update', ['role' => $apiRole, 'team' => $selected->id]), ['name' => 'No web', 'permissions' => []])->assertNotFound();
});

test('team context restores loaded permissions and team id even after failure', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $current = $user->currentTeam;
    $selected = Team::factory()->create();
    $selected->users()->attach($user);
    grantTeamPermissions($user, $current, ['read roles']);
    setPermissionsTeamId($current->id);
    $user->load('permissions', 'roles');
    $old = $user->getRelation('permissions');
    try {
        app(TeamContext::class)->run($user, $selected, function () {
            throw new RuntimeException('synthetic');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('synthetic');
    }
    expect(getPermissionsTeamId())->toBe($current->id)->and($user->getRelation('permissions'))->toBe($old)
        ->and($user->current_team_id)->toBe($current->id);
});

test('ownership alone grants no administrative powers', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = $owner->currentTeam;
    $member = User::factory()->create();
    $team->users()->attach($member);
    $this->actingAs($owner)->put(route('admin.teams.update', $team), ['name' => 'Otro'])->assertForbidden();
    $this->put('/teams/'.$team->id, ['name' => 'Otro'])->assertForbidden();
    $this->delete('/teams/'.$team->id.'/members/'.$member->id)->assertForbidden();
    $this->put(route('teams.responsible.update', $team), ['responsible_id' => $member->id, 'confirmation' => true])->assertForbidden();
});

test('responsibility transfer preserves both memberships roles and current teams and logs the selected team', function () {
    $admin = actingAsSuperAdmin();
    $team = Team::factory()->create(['user_id' => $admin->id]);
    $member = User::factory()->withPersonalTeam()->create();
    $memberCurrent = $member->currentTeam->id;
    $team->users()->attach($member);
    $role = Role::query()->create(['name' => 'Responsabilidad operativa', 'guard_name' => 'web', 'team_id' => $team->id]);
    foreach ([$admin, $member] as $assigned) {
        DB::table('model_has_roles')->insert(['role_id' => $role->id, 'model_id' => $assigned->id, 'model_type' => $assigned->getMorphClass(), 'team_id' => $team->id]);
    }
    $assignments = DB::table('model_has_roles')->where('team_id', $team->id)->orderBy('model_id')->get()->toJson();
    $this->actingAs($admin)->put(route('teams.responsible.update', $team), ['responsible_id' => $member->id, 'confirmation' => true])->assertSessionHasNoErrors();
    expect($team->fresh()->user_id)->toBe($member->id)
        ->and($admin->fresh()->belongsToTeam($team->fresh()))->toBeTrue()
        ->and($member->fresh()->belongsToTeam($team->fresh()))->toBeTrue()
        ->and($member->fresh()->current_team_id)->toBe($memberCurrent)
        ->and(DB::table('model_has_roles')->where('team_id', $team->id)->orderBy('model_id')->get()->toJson())->toBe($assignments);
    $this->assertDatabaseHas('activity_log', ['event' => 'team.responsible_transferred', 'team_id' => $team->id, 'subject_id' => $team->id]);
    $this->get(route('teams.show', $team))->assertInertia(fn (Assert $page) => $page->where('team.members_count', 2)->where('team.owner.id', $member->id));
});

test('transfer rejects inactive outsiders missing confirmation and direct update cannot deactivate a busy team', function () {
    $admin = actingAsSuperAdmin();
    $team = $admin->currentTeam;
    $member = User::factory()->create(['status' => UserStatus::Inactive]);
    $team->users()->attach($member);
    $this->actingAs($admin)->put(route('teams.responsible.update', $team), ['responsible_id' => $member->id, 'confirmation' => true])->assertSessionHasErrors('responsible_id');
    $outsider = User::factory()->create();
    $this->put(route('teams.responsible.update', $team), ['responsible_id' => $outsider->id, 'confirmation' => true])->assertSessionHasErrors('responsible_id');
    $this->put(route('teams.responsible.update', $team), ['responsible_id' => $member->id])->assertSessionHasErrors('confirmation');
    $this->put(route('admin.teams.update', $team), ['name' => $team->name, 'activo' => false])->assertSessionHasErrors('team');
    expect($team->fresh()->activo)->toBeTrue();
});

test('team activity filter authorizes selected permissions and does not expose other teams', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $current = $user->currentTeam;
    $selected = Team::factory()->create();
    $selected->users()->attach($user);
    grantTeamPermissions($user, $selected, ['read activity-log']);
    foreach ([$current, $selected] as $team) {
        activity()->tap(function ($activity) use ($team) {
            $activity->team_id = $team->id;
        })->log('Actividad '.$team->id);
    }
    $this->actingAs($user)->get(route('admin.users.activity', ['team_id' => $selected->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('activityTeam.id', $selected->id)->has('activityLogs.data', 1)->where('activityLogs.data.0.team.id', $selected->id));
    $this->get(route('admin.users.activity'))->assertForbidden();
    $this->get(route('admin.users.activity.export', ['team_id' => $current->id]))->assertForbidden();
    $this->get(route('admin.users.activity', ['team_id' => 'incorrecto']))->assertSessionHasErrors('team_id');
    expect(session('errors')->first('team_id'))->toContain('equipo seleccionado')->not->toContain('validation.');
});
