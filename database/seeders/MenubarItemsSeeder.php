<?php

namespace Database\Seeders;

use App\Models\MenubarItem;
use App\Models\MenubarItemModule;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenubarItemsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $clientes = Module::where('name', 'clientes')->firstOrFail();
            $historial = Module::where('name', 'historial-crediticio')->firstOrFail();
            $cdc = Module::where('name', 'circulo-credito')->firstOrFail();
            $dashboard = Module::where('name', 'dashboard')->firstOrFail();
            $admin = Module::where('name', 'admin')->firstOrFail();
            $users = Module::where('name', 'users')->firstOrFail();
            $roles = Module::where('name', 'roles')->firstOrFail();
            $menubarItems = Module::where('name', 'menubar-items')->firstOrFail();
            $activityLog = Module::where('name', 'activity-log')->firstOrFail();
            $configuracionEmpresa = Module::where('name', 'configuracion-empresa')->firstOrFail();
            $sucursales = Module::where('name', 'sucursales')->firstOrFail();
            $productosCrediticios = Module::where('name', 'productos-crediticios')->firstOrFail();
            $plantillasDocumentos = Module::where('name', 'plantillas-documentos')->firstOrFail();

            $items = [
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
                    'id' => 18,
                    'label' => 'Expediente KYC',
                    'icon' => 'pi pi-fw pi-folder-open',
                    'type' => 'route:name',
                    'value' => 'clientes.expediente.show',
                    'params' => json_encode(['cliente' => '{cliente}']),
                    'parent_id' => 1,
                    'sort_order' => 2,
                ],
                [
                    'id' => 7,
                    'label' => 'Listado de consultas',
                    'icon' => 'pi pi-fw pi-list',
                    'type' => 'route:name',
                    'value' => 'clientes.historial-crediticio.index',
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
                    'label' => 'Círculo de crédito',
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
                [
                    'id' => 16,
                    'label' => 'Configuración de empresa',
                    'icon' => 'pi pi-fw pi-building',
                    'type' => 'route:name',
                    'value' => 'admin.configuracion-empresa.index',
                    'params' => null,
                    'parent_id' => 10,
                    'sort_order' => 6,
                ],
                [
                    'id' => 17,
                    'label' => 'Sucursales',
                    'icon' => 'pi pi-fw pi-map-marker',
                    'type' => 'route:name',
                    'value' => 'admin.sucursales.index',
                    'params' => null,
                    'parent_id' => 10,
                    'sort_order' => 7,
                ],
                [
                    'id' => 19,
                    'label' => 'Productos crediticios',
                    'icon' => 'pi pi-fw pi-wallet',
                    'type' => 'route:name',
                    'value' => 'productos-crediticios.index',
                    'params' => null,
                    'parent_id' => null,
                    'sort_order' => 20,
                ],
                [
                    'id' => 20,
                    'label' => 'Documentos y plantillas',
                    'icon' => 'pi pi-fw pi-file-edit',
                    'type' => 'route:name',
                    'value' => 'plantillas-documentos.index',
                    'params' => null,
                    'parent_id' => null,
                    'sort_order' => 21,
                ],
            ];

            foreach ($items as $item) {
                $id = $item['id'];
                unset($item['id']);

                MenubarItem::updateOrCreate(['id' => $id], $item);
            }

            $links = [
                ['menubar_item_id' => 19, 'module_id' => $productosCrediticios->id, 'routes' => ['productos-crediticios.index']],
                ['menubar_item_id' => 20, 'module_id' => $plantillasDocumentos->id, 'routes' => ['plantillas-documentos.index']],
                ['menubar_item_id' => 1, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 18, 'module_id' => $clientes->id, 'routes' => ['clientes.edit', 'clientes.show', 'clientes.expediente.show']],
                ['menubar_item_id' => 2, 'module_id' => $clientes->id,  'routes' => ['clientes.edit']],
                ['menubar_item_id' => 3, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 4, 'module_id' => $clientes->id,  'routes' => ['clientes.show']],
                ['menubar_item_id' => 5, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 6, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 6, 'module_id' => $historial->id, 'routes' => ['clientes.historial-crediticio.index']],
                ['menubar_item_id' => 7, 'module_id' => $historial->id, 'routes' => ['clientes.historial-crediticio.index', 'clientes.historial-crediticio.show']],
                ['menubar_item_id' => 7, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 8, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 8, 'module_id' => $historial->id, 'routes' => ['clientes.historial-crediticio.index']],
                ['menubar_item_id' => 9, 'module_id' => $clientes->id,  'routes' => ['clientes.edit', 'clientes.show']],
                ['menubar_item_id' => 9, 'module_id' => $historial->id, 'routes' => ['clientes.historial-crediticio.index']],
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
                $configuracionEmpresa->id => [
                    'admin.configuracion-empresa.index',
                    'admin.configuracion-empresa.update',
                ],
                $sucursales->id => [
                    'admin.sucursales.index',
                    'admin.sucursales.store',
                    'admin.sucursales.update',
                    'admin.sucursales.destroy',
                ],
            ];

            foreach ($adminRoutesByModule as $moduleId => $routes) {
                foreach ([10, 11, 12, 13, 14, 15, 16, 17] as $menubarItemId) {
                    $links[] = [
                        'menubar_item_id' => $menubarItemId,
                        'module_id' => $moduleId,
                        'routes' => $routes,
                    ];
                }
            }

            foreach ($links as $link) {
                MenubarItemModule::updateOrCreate(
                    [
                        'menubar_item_id' => $link['menubar_item_id'],
                        'module_id' => $link['module_id'],
                    ],
                    [
                        'routes' => $link['routes'],
                    ],
                );
            }
        });
    }
}
