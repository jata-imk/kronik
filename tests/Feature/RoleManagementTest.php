<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

test('role creation ignores a supplied foreign or null team', function ($foreign) {
    $admin = actingAsSuperAdmin();
    $other = User::factory()->withPersonalTeam()->create()->ownedTeams()->first();
    $this->actingAs($admin)->post(route('admin.roles.store'), [
        'name' => 'Tesorería', 'team_id' => $foreign ? $other->id : null,
    ])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('roles', ['name' => 'Tesorería', 'team_id' => $admin->current_team_id]);
    $this->assertDatabaseMissing('roles', ['name' => 'Tesorería', 'team_id' => $foreign ? $other->id : null]);
})->with([true, false]);

test('role writes reject other teams and global templates', function ($global) {
    $admin = actingAsSuperAdmin();
    $other = User::factory()->withPersonalTeam()->create()->ownedTeams()->first();
    $role = Role::create(['name' => 'Protegido', 'guard_name' => 'web', 'team_id' => $global ? null : $other->id]);
    $this->actingAs($admin)->put(route('admin.roles.update', $role), ['name' => 'Manipulado', 'permissions' => []])->assertNotFound();
    $this->delete(route('admin.roles.destroy', $role))->assertNotFound();
    expect($role->fresh()->name)->toBe('Protegido');
})->with([true, false]);

test('role names are unique only within their team and guard on create and rename', function () {
    $admin = actingAsSuperAdmin();
    $other = User::factory()->withPersonalTeam()->create()->ownedTeams()->first();
    Role::create(['name' => 'Tesorería', 'guard_name' => 'web', 'team_id' => $other->id]);
    $this->actingAs($admin)->post(route('admin.roles.store'), ['name' => 'Tesorería'])->assertSessionHasNoErrors();
    $role = Role::where('team_id', $admin->current_team_id)->firstOrFail();
    $this->post(route('admin.roles.store'), ['name' => 'Tesorería'])->assertSessionHasErrors('name');
    $duplicate = Role::create(['name' => 'Otro rol', 'guard_name' => 'web', 'team_id' => $admin->current_team_id]);
    $this->put(route('admin.roles.update', $duplicate), ['name' => 'Tesorería'])->assertSessionHasErrors('name');
    $this->put(route('admin.roles.update', $role), ['name' => 'Tesorería'])->assertSessionHasNoErrors();
    $this->post(route('admin.roles.store'), ['name' => ''])->assertSessionHasErrors('name');
    expect(session('errors')->first('name'))->toContain('nombre del rol')->not->toContain('validation.');
});

test('a team role can share its name with a global template', function () {
    $admin = actingAsSuperAdmin();
    $template = Role::query()->create(['name' => 'Plantilla', 'guard_name' => 'web', 'team_id' => null]);
    $this->actingAs($admin)->post(route('admin.roles.store'), ['name' => 'Plantilla'])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('roles', ['name' => 'Plantilla', 'team_id' => $admin->current_team_id]);
    expect($template->fresh()->team_id)->toBeNull();
});

test('all permissions can be removed and deletion returns the correct Inertia redirect', function () {
    $admin = actingAsSuperAdmin();
    $role = Role::create(['name' => 'Temporal', 'guard_name' => 'web', 'team_id' => $admin->current_team_id]);
    $permission = Permission::firstOrCreate(['name' => 'read clientes', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    $this->actingAs($admin)->delete(route('admin.roles.destroy', $role))->assertRedirect()->assertSessionHasErrors('message');
    $this->put(route('admin.roles.update', $role), ['name' => 'Temporal', 'permissions' => []])->assertRedirect()->assertSessionHasNoErrors();
    expect($role->fresh()->permissions)->toHaveCount(0);
    $this->delete(route('admin.roles.destroy', $role))->assertRedirect()->assertSessionHas('success', 'Rol eliminado');
    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('roles with assigned users cannot be deleted and permission ids must be valid', function () {
    $admin = actingAsSuperAdmin();
    $role = Role::create(['name' => 'Operación', 'guard_name' => 'web', 'team_id' => $admin->current_team_id]);
    setPermissionsTeamId($admin->current_team_id);
    $admin->assignRole($role);
    $this->actingAs($admin)->delete(route('admin.roles.destroy', $role))->assertSessionHasErrors('message');
    $this->put(route('admin.roles.update', $role), ['name' => 'Operación', 'permissions' => [999999]])->assertSessionHasErrors('permissions.0');
    expect(session('errors')->first('permissions.0'))->not->toContain('validation.');
});

test('catalogue labels cover migrated and newly seeded permissions without changing technical names', function () {
    $this->seed(Database\Seeders\ModulesAndPermissionsSeeder::class);
    $original = Permission::where('name', 'read clientes')->firstOrFail();
    $this->seed(Database\Seeders\ModulesAndPermissionsSeeder::class);
    expect($original->fresh()->etiqueta)->toBe('Consultar clientes')
        ->and(Permission::where('name', 'read clientes')->count())->toBe(1)
        ->and(DB::table('permissions')->whereNull('etiqueta')->count())->toBe(0)
        ->and(DB::table('modules')->whereNull('descripcion')->count())->toBe(0);
    $custom = Permission::create(['name' => 'custom action', 'guard_name' => 'web']);
    expect($custom->etiqueta)->toBe('Permiso personalizado');
    $module = Module::create(['name' => 'custom-module']);
    expect($module->descripcion)->not->toBeEmpty();
});
