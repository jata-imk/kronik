<?php

use Database\Seeders\MenubarItemsSeeder;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('credit history routes resolve their configured menubar module', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $this->seed(MenubarItemsSeeder::class);
    $user = actingAsSuperAdmin();

    $this->actingAs($user)
        ->get(route('clientes.historial-crediticio.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menubarItems.0.label', 'Inicio')
            ->where('menubarItems.1.label', 'Historial Crediticio')
        );
});
