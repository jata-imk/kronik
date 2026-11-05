<?php

use App\Models\User;
use App\Services\ProductoVersionService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('consulta exacta fuera de página conserva última versión filtros y ausencia de actividad', function () {
    $actor = actingAsSuperAdmin();
    $version = solicitudProducto($actor->id);
    $service = app(ProductoVersionService::class);
    for ($i = 0; $i < 27; $i++) {
        $service->nuevaVersion($version->producto, $version, $actor->id);
    }
    $activities = \Spatie\Activitylog\Models\Activity::count();
    $this->actingAs($actor)->get(route('productos-crediticios.index', [
        'producto_id' => $version->producto_crediticio_id, 'version_id' => $version->id,
        'modo' => 'consulta', 'versiones_page' => 2, 'versiones_por_pagina' => 10,
        'buscar' => 'no coincide',
    ]))->assertInertia(fn (Assert $p) => $p->where('modoVersion', 'consulta')
        ->has('productos', 0)->where('productoSeleccionado.ultimaVersion.numero', 28)
        ->where('productoSeleccionado.versionSeleccionada.id', $version->id)
        ->where('productoSeleccionado.versionSeleccionada.puede_editar', false)
        ->where('versionesListado.current_page', 2)->where('versionesListado.total', 28));
    expect(\Spatie\Activitylog\Models\Activity::count())->toBe($activities);
});

test('lector consulta borradores pero no obtiene capacidad de escritura', function () {
    $admin = actingAsSuperAdmin();
    $active = solicitudProducto($admin->id);
    $draft = app(ProductoVersionService::class)->nuevaVersion($active->producto, $active, $admin->id);
    $reader = User::factory()->withPersonalTeam()->create();
    $reader->forceFill(['current_team_id' => $reader->ownedTeams()->first()->id])->save();
    grantTeamPermissions($reader, $reader->currentTeam, ['read productos-crediticios']);
    $this->actingAs($reader)->get(route('productos-crediticios.index', ['producto_id' => $active->producto_crediticio_id, 'version_id' => $draft->id, 'modo' => 'edicion']))
        ->assertInertia(fn (Assert $p) => $p->where('productoSeleccionado.versionSeleccionada.puede_editar', false));
    $this->putJson(route('productos-crediticios.update', [$active->producto, $draft]), [])->assertForbidden();
    $this->actingAs($admin)->get(route('productos-crediticios.index', ['producto_id' => $active->producto_crediticio_id, 'version_id' => $draft->id]))
        ->assertInertia(fn (Assert $p) => $p->where('productoSeleccionado.versionSeleccionada.puede_editar', true));
    app(ProductoVersionService::class)->registrarUso($draft, 'solicitudes', 100);
    $this->get(route('productos-crediticios.index', ['producto_id' => $active->producto_crediticio_id, 'version_id' => $draft->id]))
        ->assertInertia(fn (Assert $p) => $p->where('productoSeleccionado.versionSeleccionada.puede_editar', false));
});

test('ID ajeno o inexistente no sustituye la versión seleccionada y falta de lectura rechaza acceso', function () {
    $admin = actingAsSuperAdmin();
    $first = solicitudProducto($admin->id);
    $other = solicitudProducto($admin->id);
    foreach ([$other->id, 999999] as $id) {
        $this->actingAs($admin)->get(route('productos-crediticios.index', ['producto_id' => $first->producto_crediticio_id, 'version_id' => $id]))->assertNotFound();
    }
    $this->actingAs(User::factory()->withPersonalTeam()->create())
        ->get(route('productos-crediticios.index', ['producto_id' => $first->producto_crediticio_id, 'version_id' => $first->id]))->assertForbidden();
});
