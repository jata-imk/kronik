<?php

namespace Database\Seeders;

use App\Models\MenubarItem;
use App\Models\MenubarItemModule;
use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenubarItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
                'label' => 'Clientes',
                'icon' => null,
                'type' => 'menu',
                'route_type' => null,
                'route_name' => null,
                'route_params' => null,
                'parent_id' => null,
                'order' => 0,
            ],
            [
                'label' => 'Ver información',
                'icon' => 'pi pi-fw pi-user',
                'type' => 'route:dynamic',
                'route_type' => 'route:dynamic',
                'route_name' => '[{"condition_type":"route_regexp","condition_value":{"route_name":"clientes.edit","pregmatch_subject_type":"referer"},"route_name":"clientes.edit","params":{"cliente":"{cliente}"}},{"condition_type":"default","route_name":"clientes.edit","params":{"cliente":"{cliente}"}}]',
                'route_params' => null,
                'parent_id' => 1,
                'order' => 1,
            ],
            [
                'label' => 'Nuevo cliente',
                'icon' => 'pi pi-fw pi-user-plus',
                'type' => 'route:name',
                'route_type' => 'route:name',
                'route_name' => 'clientes.create',
                'route_params' => null,
                'parent_id' => 1,
                'order' => 2,
            ],
            [
                'label' => 'Editar',
                'icon' => 'pi pi-fw pi-user-edit',
                'type' => 'route:name',
                'route_type' => 'route:name',
                'route_name' => 'clientes.edit',
                'route_params' => json_encode(["cliente" => "{cliente}"]),
                'parent_id' => 1,
                'order' => 3,
            ],
            [
                'label' => 'Listado',
                'icon' => 'pi pi-fw pi-users',
                'type' => 'route:name',
                'route_type' => 'route:name',
                'route_name' => 'clientes.index',
                'route_params' => null,
                'parent_id' => 1,
                'order' => 4,
            ],
            [
                'label' => 'Historial Crediticio',
                'icon' => null,
                'type' => 'menu',
                'route_type' => null,
                'route_name' => null,
                'route_params' => json_encode(["cliente" => "{cliente}", "historial-crediticio" => "{historial-crediticio}"]),
                'parent_id' => null,
                'order' => 0,
            ],
            [
                'label' => 'Listado de consultas',
                'icon' => 'pi pi-fw pi-list',
                'type' => 'route:name',
                'route_type' => 'route:name',
                'route_name' => 'historial-crediticio.index',
                'route_params' => null,
                'parent_id' => 6,
                'order' => 1,
            ],
            [
                'label' => 'Nueva consulta',
                'icon' => null,
                'type' => 'menu',
                'route_type' => null,
                'route_name' => null,
                'route_params' => json_encode(["cliente" => "{cliente}", "historial-crediticio" => "{historial-crediticio}"]),
                'parent_id' => 6,
                'order' => 2,
            ],
            [
                'label' => 'Circulo de crédito',
                'icon' => 'pi pi-fw pi-plus-circle',
                'type' => 'route:name',
                'route_type' => 'route:name',
                'route_name' => 'circulo-credito.index',
                'route_params' => json_encode(["cliente" => "{cliente}", "circulo-credito" => "{circulo-credito}", "historial-crediticio" => "{historial-crediticio}"]),
                'parent_id' => 8,
                'order' => 1,
            ],
        ];

        MenuBarItem::insert($data);

        ////////////////////////
        // MenubarItemModules //
        ////////////////////////
        $data = [
            ['menubar_item_id' => 1, 'module_id' => $clientes->id,  'routes' => json_encode(["clientes.edit", "clientes.show"])],
            ['menubar_item_id' => 2, 'module_id' => $clientes->id,  'routes' => json_encode(["clientes.edit"])],
            ['menubar_item_id' => 3, 'module_id' => $clientes->id,  'routes' => json_encode(["clientes.edit", "clientes.show"])],
            ['menubar_item_id' => 4, 'module_id' => $clientes->id,  'routes' => json_encode(["clientes.show"])],
            ['menubar_item_id' => 5, 'module_id' => $clientes->id,  'routes' => json_encode(["clientes.edit", "clientes.show"])],
            ['menubar_item_id' => 6, 'module_id' => $clientes->id,  'routes' => json_encode(["clientes.edit", "clientes.show"])],
            ['menubar_item_id' => 6, 'module_id' => $historial->id, 'routes' => json_encode(["historial-crediticio.index"])],
            ['menubar_item_id' => 7, 'module_id' => $historial->id, 'routes' => json_encode(["historial-crediticio.edit", "historial-crediticio.show"])],
            ['menubar_item_id' => 7, 'module_id' => $clientes->id,  'routes' => json_encode(["clientes.edit", "clientes.show"])],
            ['menubar_item_id' => 8, 'module_id' => $clientes->id,  'routes' => json_encode(["clientes.edit", "clientes.show"])],
            ['menubar_item_id' => 8, 'module_id' => $historial->id, 'routes' => json_encode(["historial-crediticio.index"])],
            ['menubar_item_id' => 9, 'module_id' => $clientes->id,  'routes' => json_encode(["clientes.edit", "clientes.show"])],
            ['menubar_item_id' => 9, 'module_id' => $historial->id, 'routes' => json_encode(["historial-crediticio.index"])],
            ['menubar_item_id' => 9, 'module_id' => $cdc->id,       'routes' => json_encode(["circulo-credito.index"])],
        ];

        foreach ($data as $record) {
            MenubarItemModule::create($record);
        }
    }
}
