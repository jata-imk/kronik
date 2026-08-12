<?php

use App\Enums\UserStatus;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('global administrator creates a pending user with team and branch assignments', function () {
    Notification::fake();
    $admin = actingAsSuperAdmin();
    $team = $admin->currentTeam;
    $branch = Sucursal::factory()->create();
    $role = Role::create(['name' => 'Operador', 'guard_name' => 'web', 'team_id' => $team->id]);

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Usuario invitado',
        'email' => 'invitado@example.test',
        'current_team_id' => $team->id,
        'team_roles' => [['team_id' => $team->id, 'role_ids' => [$role->id]]],
        'sucursal_ids' => [$branch->id],
        'sucursal_principal_id' => $branch->id,
        'is_super_admin' => false,
    ])->assertRedirect();

    $invited = User::where('email', 'invitado@example.test')->firstOrFail();
    expect($invited->status)->toBe(UserStatus::Pending)
        ->and($invited->current_team_id)->toBe($team->id)
        ->and($invited->current_sucursal_id)->toBe($branch->id)
        ->and($invited->sucursales()->whereKey($branch)->exists())->toBeTrue();
    setPermissionsTeamId($team->id);
    expect($invited->hasRole($role))->toBeTrue();
    Notification::assertSentTo($invited, ResetPassword::class);
});

test('inactive and pending users cannot keep an authenticated session', function (UserStatus $status) {
    $user = User::factory()->withPersonalTeam()->create(['status' => $status]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
})->with([UserStatus::Pending, UserStatus::Inactive]);

test('a global administrator deactivates users without deleting their records', function () {
    $admin = actingAsSuperAdmin();
    $target = User::factory()->withPersonalTeam()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $target))->assertRedirect();

    expect($target->fresh()->status)->toBe(UserStatus::Inactive);
    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

test('team hub links can prefill the central invitation form', function () {
    $admin = actingAsSuperAdmin();
    $role = Role::create(['name' => 'Promotor', 'guard_name' => 'web', 'team_id' => $admin->current_team_id]);

    $this->actingAs($admin)
        ->get(route('admin.users.index', [
            'invite' => 1,
            'team_id' => $admin->current_team_id,
            'sucursal_id' => $admin->current_sucursal_id,
            'role_id' => $role->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->where('prefill.invite', true)
            ->where('prefill.team_id', $admin->current_team_id)
            ->where('prefill.sucursal_id', $admin->current_sucursal_id)
            ->where('prefill.role_id', $role->id));
});

test('a global administrator cannot remove their own global access', function () {
    $admin = actingAsSuperAdmin();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => 'active',
            'is_super_admin' => false,
            'current_team_id' => $admin->current_team_id,
            'team_roles' => [['team_id' => $admin->current_team_id, 'role_ids' => []]],
            'sucursal_ids' => [$admin->current_sucursal_id],
            'sucursal_principal_id' => $admin->current_sucursal_id,
        ])
        ->assertSessionHasErrors([
            'is_super_admin' => 'No puedes retirar tu propio acceso de Super Admin.',
        ]);

    expect($admin->fresh()->is_super_admin)->toBeTrue();
});

test('an administrator cannot deactivate their own account', function () {
    $admin = actingAsSuperAdmin();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertSessionHasErrors([
            'user' => 'No puedes desactivar tu propia cuenta.',
        ]);

    expect($admin->fresh()->status)->toBe(\App\Enums\UserStatus::Active);
});
