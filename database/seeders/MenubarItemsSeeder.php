<?php

namespace Database\Seeders;

use App\Models\MenubarItem;
use App\Models\MenubarItemModule;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class MenubarItemsSeeder extends Seeder
{
    // Only navigable screens/viewers: writes and JSON helper endpoints are not menu destinations.

    public function run(): void
    {
        $modules = Module::whereIn('name', array_keys(config('menubar')))->get()->keyBy('name');
        foreach (config('menubar') as $moduleName => [$label, $pages]) {
            if (! $modules->has($moduleName)) {
                throw new RuntimeException("Falta el módulo {$moduleName}. Ejecuta ModulesAndPermissionsSeeder antes de cargar el menú.");
            }
            foreach ($pages as $routeName => $pageLabel) {
                if (! Route::has($routeName) || ! in_array('GET', Route::getRoutes()->getByName($routeName)->methods(), true)) {
                    throw new RuntimeException("La página {$routeName} no está disponible. Revisa el catálogo del menú antes de sembrarlo.");
                }
            }
        }

        DB::transaction(function () use ($modules) {
            foreach (config('menubar') as $moduleName => [$label, $pages]) {
                $module = $modules->get($moduleName);
                // Menu values are ignored by the renderer; this stable key permits renaming a folder.
                $parent = MenubarItem::firstOrCreate(['type' => 'menu', 'value' => 'catalogo:'.$moduleName], [
                    'label' => $label, 'icon' => 'pi pi-'.preg_replace('/^pi-/', '', $module->icon ?? 'folder'),
                    'parent_id' => null, 'sort_order' => (int) MenubarItem::max('sort_order') + 1,
                ]);
                $this->linkWithoutActivation($parent, $module);
                foreach ($pages as $routeName => $pageLabel) {
                    $params = [];
                    $route = Route::getRoutes()->getByName($routeName);
                    foreach ($route->parameterNames() as $parameter) {
                        if (! str_contains($route->uri(), '{'.$parameter.'?}')) {
                            $params[$parameter] = '{'.$parameter.'}';
                        }
                    }
                    $item = MenubarItem::where('type', 'route:name')->where('value', $routeName)
                        ->whereHas('menubarItemModules', fn ($query) => $query->where('module_id', $module->id))
                        ->orderBy('id')->first();
                    $item ??= MenubarItem::create([
                        'parent_id' => $parent->id, 'type' => 'route:name', 'value' => $routeName,
                        'label' => $pageLabel, 'icon' => 'pi pi-arrow-right', 'params' => $params ?: null,
                        'sort_order' => (int) $parent->children()->max('sort_order') + 1,
                    ]);
                    $this->linkWithoutActivation($item, $module);
                }
            }
        });
    }

    private function linkWithoutActivation(MenubarItem $item, Module $module): void
    {
        // Re-running the seeder preserves activation routes chosen later by the administrator.
        MenubarItemModule::firstOrCreate(['menubar_item_id' => $item->id, 'module_id' => $module->id], ['routes' => []]);
    }
}
