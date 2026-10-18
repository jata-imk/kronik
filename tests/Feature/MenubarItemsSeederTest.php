<?php

use App\Models\MenubarItem;
use App\Models\MenubarItemModule;
use App\Models\Module;
use Database\Seeders\MenubarItemsSeeder;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(ModulesAndPermissionsSeeder::class);
});

it('loads all operational modules with real GET destinations and no activation routes', function () {
    $this->seed(MenubarItemsSeeder::class);
    $parents = MenubarItem::where('type', 'menu')->with('modules')->get();
    expect($parents->flatMap->modules->pluck('name')->sort()->values()->all())
        ->toBe(Module::pluck('name')->sort()->values()->all());
    expect(MenubarItemModule::count())->toBeGreaterThan(0);
    foreach (MenubarItemModule::all() as $link) {
        expect($link->routes)->toBe([]);
    }
    foreach (MenubarItem::where('type', 'route:name')->get() as $item) {
        $route = Route::getRoutes()->getByName($item->value);
        expect($route)->not->toBeNull()->and($route->methods())->toContain('GET');
        if ($route->getActionName() !== 'Closure') {
            [$controller, $method] = array_pad(explode('@', $route->getActionName(), 2), 2, '__invoke');
            expect(method_exists($controller, $method))->toBeTrue();
        }
        foreach ($route->parameterNames() as $parameter) {
            if (! str_contains($route->uri(), '{'.$parameter.'?}')) {
                expect($item->params[$parameter])->toBe('{'.$parameter.'}');
            }
        }
    }
    foreach (['admin.users.create', 'admin.roles.edit', 'admin.menubar-items.show', 'solicitudes.store',
        'creditos.pagos.store', 'documentos-generados.status', 'geocoding.search'] as $invalidDestination) {
        $this->assertDatabaseMissing('menubar_items', ['value' => $invalidDestination]);
    }
    $admin = actingAsSuperAdmin();
    foreach (['clientes.index', 'productos-crediticios.index', 'admin.menubar-items.index'] as $page) {
        $this->actingAs($admin)->get(route($page))->assertOk()->assertInertia(fn ($response) => $response
            ->has('menubarItems', 1)->where('menubarItems.0.label', 'Inicio'));
    }
});

it('does not duplicate or overwrite renamed moved or manually activated catalogue entries', function () {
    $this->seed(MenubarItemsSeeder::class);
    $folder = MenubarItem::where('value', 'catalogo:clientes')->firstOrFail();
    $folder->update(['label' => 'Mis clientes']);
    $item = $folder->children()->where('value', 'clientes.create')->firstOrFail();
    $item->update(['label' => 'Mi alta', 'icon' => 'pi pi-star', 'parent_id' => null, 'params' => ['origen' => 'mi-menu']]);
    $item->menubarItemModules()->firstOrFail()->update(['routes' => ['clientes.index']]);
    $before = DB::table('menubar_items')->orderBy('id')->get()->toJson();
    $links = DB::table('menubar_item_module')->orderBy('id')->get()->toJson();
    $this->seed(MenubarItemsSeeder::class);
    $this->seed(MenubarItemsSeeder::class);
    expect(DB::table('menubar_items')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(DB::table('menubar_item_module')->orderBy('id')->get()->toJson())->toBe($links);
});

it('preserves existing custom menus without relying on fixed database IDs', function () {
    $custom = MenubarItem::create(['label' => 'Personalizado', 'type' => 'route:static', 'value' => '/personalizado', 'sort_order' => 99]);
    $this->seed(MenubarItemsSeeder::class);
    expect($custom->fresh()->label)->toBe('Personalizado')->and($custom->fresh()->value)->toBe('/personalizado');
    expect(MenubarItem::where('value', 'catalogo:clientes')->value('id'))->not->toBe($custom->id);
});

it('checks module prerequisites before writing a partial catalogue', function () {
    Module::where('name', 'cumplimiento')->delete();
    $before = MenubarItem::count();
    expect(fn () => app(MenubarItemsSeeder::class)->run())->toThrow(RuntimeException::class, 'Falta el módulo cumplimiento.');
    $this->assertDatabaseCount('menubar_items', $before);
});
