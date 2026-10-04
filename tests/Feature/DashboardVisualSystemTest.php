<?php

use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('tablero oculta datos y accesos sin permisos', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard')
            ->where('resumen.clientes', null)
            ->where('resumen.solicitudes', null)
            ->where('resumen.creditos', null)
            ->where('accesos.clientes', false)
            ->where('accesos.creditos', false)
            ->has('pendientes', 0));
});

test('expedientes KYC tiene acceso propio y conserva filtro de sucursal', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();

    $this->actingAs($user)->get(route('clientes.expedientes.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Clientes/Index')
            ->where('view', 'expedientes')
            ->where('filters.scope', 'current'));
});

test('tablero cuenta solo clientes de la sucursal activa', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $otra = Sucursal::create(['nombre' => 'Otra sucursal QA', 'clave' => 'OTRA-QA', 'activa' => true]);
    Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    Cliente::factory()->create(['sucursal_id' => $otra->id]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('resumen.clientes', 1));
});
