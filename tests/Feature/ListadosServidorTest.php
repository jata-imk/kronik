<?php

use App\Models\MenubarItem;
use App\Models\ProductoCrediticio;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('usuarios filtra antes de paginar y conserva una edición fuera de página', function () {
    $admin = actingAsSuperAdmin();
    User::factory()->count(27)->create();
    $target = User::factory()->create(['name' => 'ZZ Objetivo de búsqueda']);
    $this->actingAs($admin)->get(route('admin.users.index', ['por_pagina' => 10, 'edit_user_id' => $target->id]))
        ->assertInertia(fn (Assert $p) => $p->has('users', 10)->where('selectedUser.id', $target->id)->where('listado.total', 29));
    $this->get(route('admin.users.index', ['buscar' => 'Objetivo']))
        ->assertInertia(fn (Assert $p) => $p->has('users', 1)->where('users.0.id', $target->id)->where('listado.total', 1));
    $this->get(route('admin.users.index', ['columnas' => '{"password":"x"}']))->assertSessionHasErrors('columnas');
    $this->get(route('admin.users.index', ['orden' => 'password']))->assertSessionHasErrors('orden');
});

test('equipos y sucursales aplican filtros de etiquetas y agregados en servidor', function () {
    $this->withoutExceptionHandling();
    $admin = actingAsSuperAdmin();
    $target = Team::factory()->create(['name' => 'Equipo buscado']);
    User::factory()->count(3)->create()->each(fn ($u) => $target->users()->attach($u));
    $target->users()->attach($target->user_id);
    $this->actingAs($admin)->get(route('admin.teams.index', ['columnas' => json_encode(['summary_search' => '4 miembros'])]))
        ->assertInertia(fn (Assert $p) => $p->where('listado.total', 1)->where('teams.0.id', $target->id));
    $branch = Sucursal::factory()->create(['nombre' => 'Sucursal fuera de página']);
    User::factory()->count(3)->create()->each(fn ($u) => $branch->users()->attach($u));
    $this->get(route('admin.sucursales.index', ['columnas' => json_encode(['summary_search' => '3 usuarios']), 'selected_sucursal_id' => $branch->id]))
        ->assertInertia(fn (Assert $p) => $p->where('listado.total', 1)->where('sucursalSeleccionada.id', $branch->id));
});

test('catálogo de productos es global y su selección no depende del filtro o página', function () {
    $admin = actingAsSuperAdmin();
    for ($i = 0; $i < 28; $i++) {
        ProductoCrediticio::create(['clave' => 'P-'.$i, 'nombre' => sprintf('Producto %02d', $i), 'activo' => true]);
    }
    $target = ProductoCrediticio::orderByDesc('id')->first();
    $this->actingAs($admin)->get(route('productos-crediticios.index', ['por_pagina' => 10, 'producto_id' => $target->id]))
        ->assertInertia(fn (Assert $p) => $p->has('productos', 10)->where('listado.total', 28)->where('productoSeleccionado.id', $target->id));
    $other = Sucursal::factory()->create();
    $admin->switchSucursal($other);
    $this->actingAs($admin->fresh())->get(route('productos-crediticios.index', ['buscar' => 'Producto 00', 'producto_id' => $target->id]))
        ->assertInertia(fn (Assert $p) => $p->has('productos', 1)->where('listado.total', 1)->where('productoSeleccionado.id', $target->id));
});

test('roles pagina el equipo seleccionado e integrantes sin ampliar el contexto', function () {
    $admin = actingAsSuperAdmin();
    $teamId = $admin->current_team_id;
    for ($i = 0; $i < 28; $i++) {
        Role::create(['name' => sprintf('Rol %02d', $i), 'guard_name' => 'web', 'team_id' => $teamId]);
    }
    $target = Role::where('team_id', $teamId)->orderByDesc('id')->first();
    $members = User::factory()->count(28)->create();
    setPermissionsTeamId($teamId);
    foreach ($members as $member) {
        $member->assignRole($target);
    }
    $this->actingAs($admin)->get(route('admin.roles.index', ['por_pagina' => 10, 'selected_role_id' => $target->id, 'integrantes_por_pagina' => 10, 'integrantes_page' => 3]))
        ->assertInertia(fn (Assert $p) => $p->has('roles', 10)->where('selectedRole.id', $target->id)
            ->has('roleMembers.'.$target->id, 8)->where('roleMembersListado.total', 28));
    $foreign = Role::create(['name' => 'Ajeno', 'guard_name' => 'web', 'team_id' => Team::factory()->create()->id]);
    $this->get(route('admin.roles.index', ['selected_role_id' => $foreign->id]))->assertNotFound();
    expect($admin->fresh()->current_team_id)->toBe($teamId);
});

test('menú pagina raíces y encuentra descendientes conservando sus ancestros', function () {
    $admin = actingAsSuperAdmin();
    for ($i = 0; $i < 28; $i++) {
        MenubarItem::create(['label' => 'Raíz '.$i, 'type' => 'route', 'value' => 'dashboard', 'sort_order' => $i]);
    }
    $root = MenubarItem::orderByDesc('id')->first();
    $child = MenubarItem::create(['label' => 'Acción descendiente única', 'type' => 'route', 'value' => 'dashboard', 'parent_id' => $root->id, 'sort_order' => 1]);
    $this->actingAs($admin)->get(route('admin.menubar-items.index', ['buscar' => 'descendiente única']))
        ->assertInertia(fn (Assert $p) => $p->where('listado.total', 1)->where('menubarCatalogo.0.id', $root->id)->where('menubarCatalogo.0.children.0.id', $child->id));
});

test('plantillas cuentan borradores fuera de la página y conservan selección independiente', function () {
    $admin = actingAsSuperAdmin();
    $service = app(\App\Services\Documentos\DocumentoPlantillaVersionService::class);
    for ($i = 0; $i < 28; $i++) {
        $target = $service->create(['clave' => 'PL-'.$i, 'nombre' => sprintf('Plantilla %02d', $i), 'tipo' => 'consentimiento_sic', 'contenido_html' => '<p>Contenido sintético</p>'], $admin->id);
    }
    $this->actingAs($admin)->get(route('plantillas-documentos.index', ['por_pagina' => 10, 'plantilla_id' => $target->id]))
        ->assertInertia(fn (Assert $p) => $p->has('plantillas', 10)->where('listado.total', 28)
            ->where('resumenListado.borradores', 28)->where('plantillaSeleccionada.id', $target->id));
});

test('opciones remotas conservan asignaciones históricas y no dependen de la página', function () {
    $admin = actingAsSuperAdmin();
    Team::factory()->count(30)->create();
    $archived = Team::factory()->create(['name' => 'ZZ Histórico', 'activo' => false]);
    $target = User::factory()->create();
    $target->teams()->attach($archived);
    $this->actingAs($admin)->getJson(route('admin.users.options', ['tipo' => 'teams', 'usuario_id' => $target->id]))
        ->assertOk()->assertJsonFragment(['id' => $archived->id, 'name' => $archived->name, 'activo' => false]);
    $this->getJson(route('admin.users.options', ['tipo' => 'teams', 'buscar' => 'ZZ Histórico']))->assertOk()->assertJsonCount(1);
    $this->getJson(route('admin.users.options', ['tipo' => 'tokens']))->assertUnprocessable();
    $admin->forceFill(['is_super_admin' => false])->save();
    $this->actingAs($admin->fresh())->getJson(route('admin.users.options', ['tipo' => 'teams']))->assertForbidden();
});

test('integrantes e invitaciones paginan independientemente y la búsqueda de responsables usa el equipo seleccionado', function () {
    $admin = actingAsSuperAdmin();
    $team = $admin->currentTeam;
    $people = User::factory()->count(28)->create();
    $team->users()->attach($people->modelKeys());
    $pending = User::factory()->count(12)->create(['status' => 'pending']);
    $team->users()->attach($pending->modelKeys());
    $target = $people->last();
    $target->update(['name' => 'ZZ Responsable fuera de página']);
    $this->actingAs($admin)->get(route('teams.show', ['team' => $team->id, 'por_pagina' => 10, 'page' => 2, 'invitaciones_por_pagina' => 10, 'invitaciones_page' => 2]))
        ->assertInertia(fn (Assert $p) => $p->has('members', 10)->has('pendingInvitations', 2)->where('team.members_count', 41)
            ->where('listado.total', 29)->where('pendingListado.total', 12));
    $this->getJson(route('teams.responsables.index', ['team' => $team->id, 'buscar' => 'ZZ Responsable']))
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $target->id);
    $admin->forceFill(['is_super_admin' => false])->save();
    grantTeamPermissions($admin, $team, ['read teams', 'read users']);
    $this->actingAs($admin->fresh())->getJson(route('teams.responsables.index', $team))->assertForbidden();
});

test('eliminar el rol seleccionado redirige a una selección válida del mismo equipo', function () {
    $admin = actingAsSuperAdmin();
    $role = Role::create(['name' => 'Temporal paginado', 'team_id' => $admin->current_team_id, 'guard_name' => 'web']);
    $this->actingAs($admin)->from(route('admin.roles.index', ['selected_role_id' => $role->id]))
        ->delete(route('admin.roles.destroy', $role))->assertRedirect(route('admin.roles.index', ['team' => $role->team_id]));
    $this->get(route('admin.roles.index', ['team' => $role->team_id]))->assertOk();
});

test('páginas históricas de versiones no sustituyen la última ni aceptan una versión de otra plantilla', function () {
    $admin = actingAsSuperAdmin();
    $product = ProductoCrediticio::create(['clave' => 'VERSIONES', 'nombre' => 'Versiones históricas', 'activo' => true]);
    for ($i = 1; $i <= 28; $i++) {
        $product->versiones()->create(['numero' => $i, 'monto_minimo' => 100, 'monto_maximo' => 1000, 'tasa_ordinaria_anual' => 36]);
    }
    $this->actingAs($admin)->get(route('productos-crediticios.index', ['producto_id' => $product->id, 'versiones_page' => 3, 'versiones_por_pagina' => 10]))
        ->assertInertia(fn (Assert $p) => $p->has('productoSeleccionado.versiones', 8)
            ->where('productoSeleccionado.versiones.0.numero', 8)->where('productoSeleccionado.ultimaVersion.numero', 28)->where('versionesListado.total', 28));
    $service = app(\App\Services\Documentos\DocumentoPlantillaVersionService::class);
    $template = $service->create(['clave' => 'V-PL', 'nombre' => 'Versiones', 'tipo' => 'consentimiento_sic', 'contenido_html' => '<p>Datos sintéticos</p>'], $admin->id);
    $first = $template->versiones()->sole();
    for ($i = 2; $i <= 28; $i++) {
        $service->duplicate($template, $first, $admin->id);
    }
    $this->get(route('plantillas-documentos.index', ['plantilla_id' => $template->id, 'version_id' => $first->id, 'versiones_page' => 3, 'versiones_por_pagina' => 10]))
        ->assertInertia(fn (Assert $p) => $p->has('plantillaSeleccionada.versiones', 8)->where('plantillaSeleccionada.versionSeleccionada.id', $first->id)
            ->where('plantillaSeleccionada.versionPreferida.numero', 28)->where('versionesListado.total', 28)->where('resumenListado.borradores', 28));
    $other = $service->create(['clave' => 'V-AJENA', 'nombre' => 'Ajena', 'tipo' => 'consentimiento_sic', 'contenido_html' => '<p>Otra</p>'], $admin->id);
    $this->get(route('plantillas-documentos.index', ['plantilla_id' => $template->id, 'version_id' => $other->versiones()->sole()->id]))->assertNotFound();
});
