<?php

use App\Models\Team;
use App\Models\User;
use App\Services\UserAdministrationData;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('team member editing supplies and preserves assignments including inactive teams', function () {
    $admin = actingAsSuperAdmin();
    $current = $admin->currentTeam;
    $other = Team::factory()->create(['activo' => false]);
    $member = User::factory()->create(['current_team_id' => $current->id, 'sucursal_principal_id' => $admin->current_sucursal_id]);
    $member->teams()->attach([$current->id, $other->id]);
    $member->sucursales()->attach($admin->current_sucursal_id);
    $inactiveBranch = \App\Models\Sucursal::factory()->create(['activa' => false]);
    $member->sucursales()->attach($inactiveBranch);
    $role = Role::query()->create(['name' => 'Histórico', 'guard_name' => 'web', 'team_id' => $other->id]);
    DB::table('model_has_roles')->insert(['role_id' => $role->id, 'team_id' => $other->id, 'model_id' => $member->id, 'model_type' => $member->getMorphClass()]);
    $this->actingAs($admin)->get(route('teams.show', $current))->assertInertia(fn (Assert $page) => $page
        ->has('members.1.team_roles', 2)->has('userFormOptions.teams')->where('members.1.team_roles.1.role_ids.0', $role->id));
    $data = app(UserAdministrationData::class)->user($member->fresh());
    $this->from(route('teams.show', $current))->put(route('admin.users.update', $member), [...$data, 'name' => 'Nombre corregido'])
        ->assertRedirect(route('teams.show', $current))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('model_has_roles', ['role_id' => $role->id, 'model_id' => $member->id, 'team_id' => $other->id]);
    expect($member->fresh()->teams)->toHaveCount(2);
    $this->assertDatabaseHas('sucursal_user', ['user_id' => $member->id, 'sucursal_id' => $inactiveBranch->id]);
    $newInactive = Team::factory()->create(['activo' => false]);
    $this->put(route('admin.users.update', $member), [...$data, 'team_roles' => [...$data['team_roles'], ['team_id' => $newInactive->id, 'role_ids' => []]]])->assertSessionHasErrors('team_roles');
});

test('removing a member clears only that teams membership roles and direct permissions', function () {
    $admin = actingAsSuperAdmin();
    $team = $admin->currentTeam;
    $member = User::factory()->withPersonalTeam()->create();
    $alternative = $member->currentTeam;
    $team->users()->attach($member);
    foreach ([$team, $alternative] as $assignment) {
        $role = Role::query()->create(['name' => 'Asignado', 'guard_name' => 'web', 'team_id' => $assignment->id]);
        DB::table('model_has_roles')->insert(['role_id' => $role->id, 'team_id' => $assignment->id, 'model_id' => $member->id, 'model_type' => $member->getMorphClass()]);
        grantTeamPermissions($member, $assignment, ['read roles']);
    }
    $this->actingAs($admin)->from(route('teams.show', $team))->delete(route('team-members.destroy', [$team, $member]))->assertRedirect(route('teams.show', $team))->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('team_user', ['team_id' => $team->id, 'user_id' => $member->id]);
    foreach (['model_has_roles', 'model_has_permissions'] as $table) {
        expect(DB::table($table)->where('model_id', $member->id)->where('team_id', $team->id)->count())->toBe(0)
            ->and(DB::table($table)->where('model_id', $member->id)->where('team_id', $alternative->id)->count())->toBe(1);
    }
    expect($member->fresh()->current_team_id)->toBe($alternative->id)->and($member->fresh()->isActive())->toBeTrue();
});

test('removal rejects responsible current and only team memberships with Spanish errors', function () {
    $admin = actingAsSuperAdmin();
    $team = $admin->currentTeam;
    $member = User::factory()->create(['current_team_id' => $team->id]);
    $team->users()->attach($member);
    $this->actingAs($admin)->delete(route('team-members.destroy', [$team, $member]))->assertSessionHasErrorsIn('removeTeamMember', 'team');
    expect(session('errors')->getBag('removeTeamMember')->first('team'))->toContain('Asigna otro equipo activo')->not->toContain('validation.');
    $member->forceFill(['current_team_id' => null])->save();
    $this->delete(route('team-members.destroy', [$team, $member]))->assertSessionHasErrorsIn('removeTeamMember', 'team');
    $this->delete(route('team-members.destroy', [$team, $admin]))->assertSessionHasErrorsIn('removeTeamMember', 'team');
    expect($team->fresh()->users)->toHaveCount(1);
});

test('team membership administration requires selected team permissions', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();
    $team = $owner->currentTeam;
    $team->users()->attach($member);
    $this->actingAs($owner)->delete(route('team-members.destroy', [$team, $member]))->assertForbidden();
    grantTeamPermissions($owner, $team, ['remove members teams']);
    $this->delete(route('team-members.destroy', [$team, $member]))->assertRedirect();
    expect($team->fresh()->users)->toHaveCount(0);
});

test('team user payload batches relation role and invitation queries as membership grows', function () {
    $admin = actingAsSuperAdmin();
    $team = $admin->currentTeam;
    $small = User::factory()->count(2)->create(['status' => \App\Enums\UserStatus::Pending]);
    $large = User::factory()->count(30)->create(['status' => \App\Enums\UserStatus::Pending]);
    $team->users()->attach($small->modelKeys());
    $team->users()->attach($large->modelKeys());
    $service = app(UserAdministrationData::class);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $service->users($small);
    $smallCount = count(DB::getQueryLog());
    DB::flushQueryLog();
    $result = $service->users($large);
    $largeCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($result)->toHaveCount(30)->and($largeCount)->toBeLessThanOrEqual($smallCount)
        ->and($largeCount)->toBeLessThan(12);
});
