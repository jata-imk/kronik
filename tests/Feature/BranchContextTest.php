<?php

use App\Enums\UserStatus;
use App\Models\Cliente;
use App\Models\Permission;
use App\Models\Sucursal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function branchUser(array $permissions, array $branches): User
{
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->ownedTeams()->firstOrFail();
    $user->sucursales()->attach(collect($branches)->pluck('id'));
    $user->forceFill([
        'current_team_id' => $team->id,
        'sucursal_principal_id' => $branches[0]->id,
        'current_sucursal_id' => $branches[0]->id,
        'status' => UserStatus::Active,
    ])->save();

    setPermissionsTeamId($team->id);
    $permissionModels = collect($permissions)->map(fn ($name) => Permission::findOrCreate($name, 'web'));
    $role = Role::create(['name' => 'Operativo '.str()->random(8), 'guard_name' => 'web', 'team_id' => $team->id]);
    $role->syncPermissions($permissionModels);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

test('a user can only switch to an assigned active branch', function () {
    $assigned = Sucursal::factory()->create();
    $other = Sucursal::factory()->create();
    $inactive = Sucursal::factory()->inactive()->create();
    $user = branchUser([], [$assigned, $inactive]);

    $this->actingAs($user)->put(route('current-sucursal.update'), ['sucursal_id' => $other->id])->assertForbidden();
    $this->actingAs($user)->put(route('current-sucursal.update'), ['sucursal_id' => $inactive->id])->assertForbidden();
    $this->actingAs($user)->put(route('current-sucursal.update'), ['sucursal_id' => $assigned->id])->assertRedirect();
});

test('global administrators can switch to any active branch', function () {
    $user = actingAsSuperAdmin();
    $other = Sucursal::factory()->create();

    $this->actingAs($user)
        ->put(route('current-sucursal.update'), ['sucursal_id' => $other->id])
        ->assertRedirect();

    expect($user->fresh()->current_sucursal_id)->toBe($other->id);
});

test('client reading is cross branch while writes use the current responsible branch', function () {
    [$first, $second] = [Sucursal::factory()->create(), Sucursal::factory()->create()];
    $user = branchUser(['read clientes', 'update clientes', 'delete clientes'], [$first, $second]);
    $local = Cliente::factory()->create(['sucursal_id' => $first->id]);
    $remote = Cliente::factory()->create(['sucursal_id' => $second->id]);

    expect($user->can('view', $remote))->toBeTrue()
        ->and($user->can('update', $local))->toBeTrue()
        ->and($user->can('update', $remote))->toBeFalse();

    $this->actingAs($user)
        ->get(route('clientes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Clientes/Index')
            ->has('clientes', 1)
            ->where('clientes.0.id', $local->id));

    $this->actingAs($user)->get(route('clientes.edit', $remote))->assertForbidden();
});

test('authorized operators can transfer a client only between assigned branches', function () {
    [$first, $second, $other] = [Sucursal::factory()->create(), Sucursal::factory()->create(), Sucursal::factory()->create()];
    $user = branchUser(['transfer clientes'], [$first, $second]);
    $cliente = Cliente::factory()->create(['sucursal_id' => $first->id]);

    $this->actingAs($user)
        ->patch(route('clientes.sucursal.transfer', $cliente), ['sucursal_id' => $other->id])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('clientes.sucursal.transfer', $cliente), ['sucursal_id' => $second->id])
        ->assertRedirect();

    expect($cliente->fresh()->sucursal_id)->toBe($second->id);
});

test('a branch with active users or clients cannot be deactivated', function () {
    $admin = actingAsSuperAdmin();
    $branch = Sucursal::factory()->create();
    $user = branchUser([], [$branch]);
    Cliente::factory()->create(['sucursal_id' => $branch->id]);

    $this->actingAs($admin)
        ->delete(route('admin.sucursales.destroy', $branch))
        ->assertSessionHasErrors('sucursal');

    expect($branch->fresh()->activa)->toBeTrue()
        ->and($user->fresh()->status)->toBe(UserStatus::Active);
});
