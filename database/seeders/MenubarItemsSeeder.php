<?php

namespace Database\Seeders;

use App\Models\MenubarItem;
use App\Models\MenubarItemModule;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenubarItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create transaction
        DB::transaction(function () {
            // ///////////
            // Módulos //
            // ///////////
            $clientes = Module::where('name', 'clientes')->first();
            $historial = Module::where('name', 'historial-crediticio')->first();
            $cdc = Module::where('name', 'circulo-credito')->first();
            $dashboard = Module::where('name', 'dashboard')->first();
            $admin = Module::where('name', 'admin')->first();
            $users = Module::where('name', 'users')->first();
            $roles = Module::where('name', 'roles')->first();
            $menubarItems = Module::where('name', 'menubar-items')->first();
            $activityLog = Module::where('name', 'activity-log')->first();

            // ////////////////
            // MenuBarItems //
            // ////////////////
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
                    'params' => json_encode(['cliente' => '{cliente}']),
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
                    'params' => json_encode(['cliente' => '{cliente}', 'historial-crediticio' => '{historial-crediticio}']),
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
                    'params' => json_encode(['cliente' => '{cliente}', 'historial-crediticio' => '{historial-crediticio}']),
                    'parent_id' => 6,
                    'sort_order' => 2,
                ],
                [
                    'id' => 9,
                    'label' => 'Circulo de crédito',
                    'icon' => 'pi pi-fw pi-plus-circle',
                    'type' => 'route:name',
                    'value' => 'circulo-credito.index',
                    'params' => json_encode(['cliente' => '{cliente}', 'circulo-credito' => '{circulo-credito}', 'historial-crediticio' => '{historial-crediticio}']),
                    'parent_id' => 8,
                    'sort_order' => 1,
                ],
                [
                    'id' => 10,
                    'label' => 'Administración',
                    'icon' => null,
                    'type' => 'menu',
                    'value' => null,
                    'params' => null,
                    'parent_id' => null,
                    'sort_order' => 90,
                ],
                [
                    'id' => 11,
                    'label' => 'Panel admin',
                    'icon' => 'pi pi-fw pi-shield',
                    'type' => 'route:name',
                    'value' => 'admin.dashboard',
                    'params' => null,
                    'parent_id' => 10,
                    'sort_order' => 1,
                ],
                [
                    'id' => 12,
                    'label' => 'Usuarios',
                    'icon' => 'pi pi-fw pi-users',
                    'type' => 'route:name',
                    'value' => 'admin.users.index',
                    'params' => null,
                    'parent_id' => 10,
                    'sort_order' => 2,
                ],
                [
                    'id' => 13,
                    'label' => 'Roles y permisos',
                    'icon' => 'pi pi-fw pi-key',
                    'type' => 'route:name',
                    'value' => 'admin.roles.index',
                    'params' => null,
                    'parent_id' => 10,
                    'sort_order' => 3,
                ],
                [
                    'id' => 14,
                    'label' => 'Menú',
                    'icon' => 'pi pi-fw pi-bars',
                    'type' => 'route:name',
                    'value' => 'admin.menubar-items.index',
                    'params' => null,
                    'parent_id' => 10,
                    'sort_order' => 4,
                ],
                [
                    'id' => 15,
                    'label' => 'Actividad',
                    'icon' => 'pi pi-fw pi-history',
                    'type' => 'route:name',
                    'value' => 'admin.users.activity',
                    'params' => null,
                    'parent_id' => 10,
                    'sort_order' => 5,
                ],
            ];

            foreach ($data as $record) {
                $id = $record['id'];
                unset($record['id']);

                MenuBarItem::updateOrCreate(['id' => $id], $record);
            }

            // //////////////////////
            // MenubarItemModules //
            // //////////////////////
            $data = [
                ['menubar_item_id' => 1, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 2, 'module_id' => $clientes->id,  'routes' => ['clientes.edit']],
                ['menubar_item_id' => 3, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 4, 'module_id' => $clientes->id,  'routes' => ['clientes.show']],
                ['menubar_item_id' => 5, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 6, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 6, 'module_id' => $historial->id, 'routes' => ['historial-crediticio.index']],
                ['menubar_item_id' => 7, 'module_id' => $historial->id, 'routes' => ['historial-crediticio.edit', 'historial-crediticio.show']],
                ['menubar_item_id' => 7, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 8, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 8, 'module_id' => $historial->id, 'routes' => ['historial-crediticio.index']],
                ['menubar_item_id' => 9, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 9, 'module_id' => $historial->id, 'routes' => ['historial-crediticio.index']],
                ['menubar_item_id' => 9, 'module_id' => $cdc->id,       'routes' => ['circulo-credito.index']],
            ];

            $adminRoutesByModule = [
                $dashboard->id => ['dashboard'],
                $admin->id => ['admin.dashboard'],
                $users->id => ['admin.users.index', 'admin.users.show', 'admin.users.create', 'admin.users.edit'],
                $roles->id => ['admin.roles.index', 'admin.roles.show', 'admin.roles.create', 'admin.roles.edit'],
                $menubarItems->id => [
                    'admin.menubar-items.index',
                    'admin.menubar-items.show',
                    'admin.menubar-items.create',
                    'admin.menubar-items.edit',
                ],
                $activityLog->id => ['admin.users.activity'],
            ];

            foreach ($adminRoutesByModule as $moduleId => $routes) {
                foreach ([10, 11, 12, 13, 14, 15] as $menubarItemId) {
                    $data[] = [
                        'menubar_item_id' => $menubarItemId,
                        'module_id' => $moduleId,
                        'routes' => $routes,
                    ];
                }
            }

            foreach ($data as $record) {
                MenubarItemModule::updateOrCreate(
                    [
                        'menubar_item_id' => $record['menubar_item_id'],
                        'module_id' => $record['module_id'],
                    ],
                    [
                        'routes' => $record['routes'],
                    ]
                );
            }
        });
    }
}
