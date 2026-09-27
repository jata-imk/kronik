<?php

namespace Database\Seeders;

use App\Models\MenubarItem;
use App\Models\MenubarItemModule;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Additive upgrade: never reapply the general menu seed over a customized menu. */
class ClienteAccesosMenubarSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $module = Module::where('name', 'clientes')->first();
            if (! $module) {
                return;
            }

            foreach ([
                'clientes.expediente.show' => ['Abrir expediente', 'pi pi-folder-open', ['cliente' => '{cliente}']],
                'solicitudes.create' => ['Nueva solicitud', 'pi pi-file-edit', null],
            ] as $route => [$label, $icon, $params]) {
                $item = MenubarItem::where('type', 'route:name')->where('value', $route)
                    ->whereHas('menubarItemModules', fn ($q) => $q->where('module_id', $module->id))
                    ->orderBy('id')->first();
                if (! $item) {
                    $item = MenubarItem::create([
                        'label' => $label, 'icon' => $icon, 'type' => 'route:name',
                        'value' => $route, 'params' => $params, 'parent_id' => null,
                        'sort_order' => (int) MenubarItem::max('sort_order') + 1,
                    ]);
                } elseif ($item->parent_id !== null) {
                    // These actions now belong in the top strip, not the client dropdown.
                    $item->update(['parent_id' => null]);
                }

                $link = MenubarItemModule::firstOrNew(['menubar_item_id' => $item->id, 'module_id' => $module->id]);
                $link->routes = array_values(array_unique([
                    ...($link->routes ?? []), 'clientes.show', 'clientes.edit',
                ]));
                $link->save();
            }
        });
    }
}
