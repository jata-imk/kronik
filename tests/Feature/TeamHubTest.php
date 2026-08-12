<?php

use App\Models\Permission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('global administrator sees the complete team hub and keeps global navigation permissions', function () {
    $admin = actingAsSuperAdmin();
    Permission::firstOrCreate(['name' => 'access admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'read clientes', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->get(route('teams.show', $admin->current_team_id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teams/Show')
            ->where('team.id', $admin->current_team_id)
            ->where('team.personal_team', true)
            ->where('canManageUsers', true)
            ->has('members', 1)
            ->where('members.0.id', $admin->id)
            ->where('auth.is_super_admin', true)
            ->where('auth.permissions.access-admin', true)
            ->where('auth.permissions.read-clientes', true));
});

test('admin surfaces reject users without their backend permissions', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.menubar-items.index'))->assertForbidden();
});
