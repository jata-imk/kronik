<?php

use App\Models\Cliente;
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

test('client menubar actions resolve the current client URLs', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $this->seed(MenubarItemsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();

    foreach (['clientes.show', 'clientes.edit'] as $routeName) {
        $this->actingAs($user)
            ->get(route($routeName, $cliente))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('menubarItems', function ($items) use ($cliente) {
                    $flatten = function ($menu) use (&$flatten) {
                        return collect($menu)->flatMap(fn ($item) => [
                            $item,
                            ...$flatten($item['items'] ?? []),
                        ]);
                    };
                    $urls = $flatten($items)->pluck('url')->filter();

                    return $urls->contains(route('clientes.edit', $cliente))
                        && $urls->contains(route('clientes.expediente.show', $cliente))
                        && $urls->contains(route('clientes.index'));
                })
            );
    }
});
