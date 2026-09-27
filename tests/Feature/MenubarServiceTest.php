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

test('el catálogo administrativo no sobrescribe las rutas del menú compartido', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $this->seed(MenubarItemsSeeder::class);
    $user = actingAsSuperAdmin();
    $this->actingAs($user)->get(route('admin.menubar-items.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('menubarCatalogo')
            ->where('menubarItems.0.label', 'Inicio')
            ->where('menubarItems.0.url', route('dashboard'))
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

test('los accesos principales del cliente precargan solicitud sin duplicar el menú personalizado', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $this->seed(MenubarItemsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $shortcut = \App\Models\MenubarItem::where('value', 'solicitudes.create')->firstOrFail();
    $shortcut->update(['label' => 'Mi solicitud', 'params' => ['cliente_id' => '{cliente}']]);
    $count = \App\Models\MenubarItem::count();
    $this->seed(\Database\Seeders\ClienteAccesosMenubarSeeder::class);
    $this->seed(\Database\Seeders\ClienteAccesosMenubarSeeder::class);
    expect(\App\Models\MenubarItem::count())->toBe($count);
    expect($shortcut->fresh()->label)->toBe('Mi solicitud');

    foreach (['clientes.show', 'clientes.edit'] as $routeName) {
        $this->actingAs($user)->get(route($routeName, $cliente))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('menubarItems', function ($items) use ($cliente) {
                $urls = collect($items)->pluck('url');

                return $urls->contains(route('solicitudes.create', ['cliente_id' => $cliente->id]))
                    && $urls->contains(route('clientes.expediente.show', $cliente))
                    && collect($items)->where('label', 'Mi solicitud')->count() === 1;
            }));
    }
});

test('el acceso a solicitud respeta permisos y sucursal incluso para superadministradores', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $this->seed(MenubarItemsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);

    $assertHidden = function () use ($user, $cliente) {
        $this->actingAs($user)->get(route('clientes.show', $cliente))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('menubarItems', fn ($items) => ! collect($items)->contains(fn ($item) => str_contains($item['url'] ?? '', '/solicitudes/create'))));
        $this->get(route('solicitudes.create', ['cliente_id' => $cliente->id]))->assertForbidden();
    };
    $user->forceFill(['current_sucursal_id' => null])->save();
    $assertHidden();
    $user->forceFill(['current_sucursal_id' => \App\Models\Sucursal::factory()->create()->id])->save();
    $assertHidden();
    $user->forceFill(['is_super_admin' => false, 'current_sucursal_id' => $cliente->sucursal_id])->save();
    $user->givePermissionTo('read clientes');
    $assertHidden();
    $user->givePermissionTo(['read solicitudes', 'create solicitudes']);
    $this->get(route('solicitudes.create', ['cliente_id' => $cliente->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('clienteInicial.id', $cliente->id));
});
