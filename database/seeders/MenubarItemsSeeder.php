<?php

namespace Database\Seeders;

use App\Models\MenubarItem;
use App\Models\MenubarItemModule;
use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// TODO: Update the seeder to use the new Module and MenubarItemModule models
class MenubarItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create transaction
        DB::transaction(function () {
            /////////////
            // Módulos //
            /////////////
            $clientes = Module::where('name', 'clientes')->first();
            $historial = Module::where('name', 'historial-crediticio')->first();
            $cdc = Module::where('name', 'circulo-credito')->first();

            //////////////////
            // MenuBarItems //
            //////////////////
            $data = [
                [
                    'id' => 1,
                    'label' => 'Clientes',
                    'icon' => null,
                    'type' => 'menu',
                    'value' => null,
                    'params' => null,
                    'parent_id' => null,
                    'sort_order' => 0,
                ],
                [
                    'id' => 2,
                    'label' => 'Ver información',
                    'icon' => 'pi pi-fw pi-user',
                    'type' => 'route:dynamic',
                    'value' => '[{"condition_type":"route_regexp","condition_value": {"route_name":"clientes.edit","pregmatch_subject_type":"referer"},"route_name":"clientes.edit","params":"{\"cliente\":\"{cliente}\"}"},{ "condition_type":"default", "route_name":"clientes.edit", "params":"{\"cliente\":\"{cliente}\"}"}]',
                    'params' => null,
                    'parent_id' => 1,
                    'sort_order' => 1,
                ],
                [
                    'id' => 3,
                    'label' => 'Nuevo cliente',
                    'icon' => 'pi pi-fw pi-user-plus',
                    'type' => 'route:name',
                    'value' => 'clientes.create',
                    'params' => null,
                    'parent_id' => 1,
                    'sort_order' => 2,
                ],
                [
                    'id' => 4,
                    'label' => 'Editar',
                    'icon' => 'pi pi-fw pi-user-edit',
                    'type' => 'route:name',
                    'value' => 'clientes.edit',
                    'params' => json_encode(["cliente" => "{cliente}"]),
                    'parent_id' => 1,
                    'sort_order' => 3,
                ],
                [
                    'id' => 5,
                    'label' => 'Listado',
                    'icon' => 'pi pi-fw pi-users',
                    'type' => 'route:name',
                    'value' => 'clientes.index',
                    'params' => null,
                    'parent_id' => 1,
                    'sort_order' => 4,
                ],
                [
                    'id' => 6,
                    'label' => 'Historial Crediticio',
                    'icon' => null,
                    'type' => 'menu',
                    'value' => null,
                    'params' => json_encode(["cliente" => "{cliente}", "historial-crediticio" => "{historial-crediticio}"]),
                    'parent_id' => null,
                    'sort_order' => 0,
                ],
                [
                    'id' => 7,
                    'label' => 'Listado de consultas',
                    'icon' => 'pi pi-fw pi-list',
                    'type' => 'route:name',
                    'value' => 'historial-crediticio.index',
                    'params' => null,
                    'parent_id' => 6,
                    'sort_order' => 1,
                ],
                [
                    'id' => 8,
                    'label' => 'Nueva consulta',
                    'icon' => null,
                    'type' => 'menu',
                    'value' => null,
                    'params' => json_encode(["cliente" => "{cliente}", "historial-crediticio" => "{historial-crediticio}"]),
                    'parent_id' => 6,
                    'sort_order' => 2,
                ],
                [
                    'id' => 9,
                    'label' => 'Circulo de crédito',
                    'icon' => 'pi pi-fw pi-plus-circle',
                    'type' => 'route:name',
                    'value' => 'circulo-credito.index',
                    'params' => json_encode(["cliente" => "{cliente}", "circulo-credito" => "{circulo-credito}", "historial-crediticio" => "{historial-crediticio}"]),
                    'parent_id' => 8,
                    'sort_order' => 1,
                ],
            ];

            MenuBarItem::insert($data);

            ////////////////////////
            // MenubarItemModules //
            ////////////////////////
            $data = [
                ['menubar_item_id' => 1, 'module_id' => $clientes->id,  'routes' => ["clientes.edit", "clientes.show"]],
                ['menubar_item_id' => 2, 'module_id' => $clientes->id,  'routes' => ["clientes.edit"]],
                ['menubar_item_id' => 3, 'module_id' => $clientes->id,  'routes' => ["clientes.edit", "clientes.show"]],
                ['menubar_item_id' => 4, 'module_id' => $clientes->id,  'routes' => ["clientes.show"]],
                ['menubar_item_id' => 5, 'module_id' => $clientes->id,  'routes' => ["clientes.edit", "clientes.show"]],
                ['menubar_item_id' => 6, 'module_id' => $clientes->id,  'routes' => ["clientes.edit", "clientes.show"]],
                ['menubar_item_id' => 6, 'module_id' => $historial->id, 'routes' => ["historial-crediticio.index"]],
                ['menubar_item_id' => 7, 'module_id' => $historial->id, 'routes' => ["historial-crediticio.edit", "historial-crediticio.show"]],
                ['menubar_item_id' => 7, 'module_id' => $clientes->id,  'routes' => ["clientes.edit", "clientes.show"]],
                ['menubar_item_id' => 8, 'module_id' => $clientes->id,  'routes' => ["clientes.edit", "clientes.show"]],
                ['menubar_item_id' => 8, 'module_id' => $historial->id, 'routes' => ["historial-crediticio.index"]],
                ['menubar_item_id' => 9, 'module_id' => $clientes->id,  'routes' => ["clientes.edit", "clientes.show"]],
                ['menubar_item_id' => 9, 'module_id' => $historial->id, 'routes' => ["historial-crediticio.index"]],
                ['menubar_item_id' => 9, 'module_id' => $cdc->id,       'routes' => ["circulo-credito.index"]],
            ];

            foreach ($data as $record) {
                MenubarItemModule::create($record);
            }
        });
    }
}
