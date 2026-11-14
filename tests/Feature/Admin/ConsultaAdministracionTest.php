<?php

use App\Models\Sucursal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->withoutVite();
});

test('lector consulta usuario exacto fuera de filtros y no puede modificarlo', function () {
    $actor = User::factory()->withPersonalTeam()->create();
    $actor->forceFill(['current_team_id' => $actor->ownedTeams()->first()->id])->save();
    grantTeamPermissions($actor, $actor->currentTeam, ['read users']);
    $selected = User::factory()->withPersonalTeam()->create();
    $count = Activity::count();
    $this->actingAs($actor)->get(route('admin.users.index', ['edit_user_id' => $selected->id, 'modo' => 'edicion', 'buscar' => 'excluido', 'page' => 2]))
        ->assertInertia(fn (Assert $p) => $p->where('selectedUser.id', $selected->id)->where('modoUsuario', 'consulta')->has('users', 0)
            ->missing('selectedUser.password')->missing('selectedUser.two_factor_secret'));
    $this->putJson(route('admin.users.update', $selected), ['name' => 'Alterado'])->assertForbidden();
    expect($selected->fresh()->name)->toBe($selected->name)->and(Activity::count())->toBe($count);
    $this->actingAs(User::factory()->withPersonalTeam()->create())->get(route('admin.users.index', ['edit_user_id' => $selected->id]))->assertForbidden();
});

test('lector consulta sucursal inactiva seleccionada fuera de página sin alterar folios', function () {
    $actor = User::factory()->withPersonalTeam()->create();
    $actor->forceFill(['current_team_id' => $actor->ownedTeams()->first()->id])->save();
    grantTeamPermissions($actor, $actor->currentTeam, ['read sucursales']);
    $selected = Sucursal::create(['nombre' => 'Archivo', 'clave' => 'ARCH', 'activa' => false, 'domicilio' => ['calle' => 'Guardada'], 'horario' => ['domingo' => 'Cerrado']]);
    $before = $selected->fresh()->toArray();
    $count = Activity::count();
    $this->actingAs($actor)->get(route('admin.sucursales.index', ['selected_sucursal_id' => $selected->id, 'modo' => 'consulta', 'buscar' => 'excluido', 'page' => 2]))
        ->assertInertia(fn (Assert $p) => $p->where('sucursalSeleccionada.id', $selected->id)->where('sucursalSeleccionada.horario.domingo', 'Cerrado')->where('modoSucursal', 'consulta')->has('sucursales', 0));
    $this->putJson(route('admin.sucursales.update', $selected), ['nombre' => 'Alterado'])->assertForbidden();
    expect($selected->fresh()->toArray())->toBe($before)->and(Activity::count())->toBe($count);
    $this->actingAs(User::factory()->withPersonalTeam()->create())->get(route('admin.sucursales.index', ['selected_sucursal_id' => $selected->id]))->assertForbidden();
    $admin = actingAsSuperAdmin();
    $this->actingAs($admin)->get(route('admin.sucursales.index', ['selected_sucursal_id' => 999999, 'modo' => 'consulta']))->assertNotFound();
});
