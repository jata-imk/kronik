<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('role members are limited to the current team', function () {
    $admin = actingAsSuperAdmin();
    $teamId = $admin->current_team_id;
    $role = Role::create(['name' => 'Revisión QA', 'guard_name' => 'web', 'team_id' => $teamId]);
    $member = User::factory()->create(['name' => 'Miembro del equipo']);
    setPermissionsTeamId($teamId);
    $member->assignRole($role);

    $otherAdmin = actingAsSuperAdmin();
    $otherTeamId = $otherAdmin->current_team_id;
    $otherRole = Role::create(['name' => 'Revisión QA', 'guard_name' => 'web', 'team_id' => $otherTeamId]);
    $otherMember = User::factory()->create(['name' => 'Miembro ajeno']);
    setPermissionsTeamId($otherTeamId);
    $otherMember->assignRole($otherRole);

    setPermissionsTeamId($teamId);
    $this->actingAs($admin)->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->where("roleMembers.{$role->id}.0.name", 'Miembro del equipo')
            ->missing("roleMembers.{$otherRole->id}"));
});
