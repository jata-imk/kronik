<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class ModulesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $dashboard = Module::updateOrCreate(
            ['name' => 'dashboard'],
            [
                'icon' => 'pi-home',
                'route_name' => 'dashboard',
                'parent_id' => null,
            ],
        );

        $admin = Module::updateOrCreate(
            ['name' => 'admin'],
            [
                'icon' => 'pi-shield',
                'route_name' => 'admin.dashboard',
                'parent_id' => null,
            ],
        );

        $users = Module::updateOrCreate(
            ['name' => 'users'],
            [
                'icon' => 'pi-user',
                'route_name' => 'admin.users',
                'parent_id' => $admin->id,
            ],
        );

        $roles = Module::updateOrCreate(
            ['name' => 'roles'],
            [
                'icon' => 'pi-key',
                'route_name' => 'admin.roles',
                'parent_id' => $admin->id,
            ],
        );

        $menubarItems = Module::updateOrCreate(
            ['name' => 'menubar-items'],
            [
                'icon' => 'pi-bars',
                'route_name' => 'admin.menubar-items',
                'parent_id' => $admin->id,
            ],
        );

        $activityLog = Module::updateOrCreate(
            ['name' => 'activity-log'],
            [
                'icon' => 'pi-history',
                'route_name' => 'admin.users.activity',
                'parent_id' => $admin->id,
            ],
        );

        $configuracionEmpresa = Module::updateOrCreate(
            ['name' => 'configuracion-empresa'],
            [
                'icon' => 'pi-building',
                'route_name' => 'admin.configuracion-empresa',
                'parent_id' => $admin->id,
            ],
        );

        $sucursales = Module::updateOrCreate(
            ['name' => 'sucursales'],
            [
                'icon' => 'pi-map-marker',
                'route_name' => 'admin.sucursales',
                'parent_id' => $configuracionEmpresa->id,
            ],
        );

        $clientes = Module::updateOrCreate(
            ['name' => 'clientes'],
            [
                'icon' => 'pi-users',
                'route_name' => 'clientes',
                'parent_id' => null,
            ],
        );

        $productosCrediticios = Module::updateOrCreate(
            ['name' => 'productos-crediticios'],
            [
                'icon' => 'pi-wallet',
                'route_name' => 'productos-crediticios.index',
                'parent_id' => null,
            ],
        );

        $historial = Module::updateOrCreate(
            ['name' => 'historial-crediticio'],
            [
                'icon' => 'pi-list',
                'route_name' => 'historial-crediticio',
                'parent_id' => $clientes->id,
            ],
        );

        $circuloCredito = Module::updateOrCreate(
            ['name' => 'circulo-credito'],
            [
                'icon' => 'pi-list',
                'route_name' => 'circulo-credito',
                'parent_id' => $historial->id,
            ],
        );

        $teams = Module::updateOrCreate(
            ['name' => 'teams'],
            [
                'icon' => 'pi-sitemap',
                'route_name' => 'teams',
                'parent_id' => null,
            ],
        );

        $actionsPerModule = [
            'dashboard' => ['read'],
            'admin' => ['access'],
            'users' => ['create', 'read', 'update', 'delete'],
            'roles' => ['create', 'read', 'update', 'delete'],
            'menubar-items' => ['create', 'read', 'update', 'delete'],
            'activity-log' => ['read'],
            'configuracion-empresa' => ['read', 'update'],
            'sucursales' => ['create', 'read', 'update', 'delete'],
            'clientes' => ['create', 'read', 'update', 'delete', 'transfer'],
            'productos-crediticios' => ['create', 'read', 'update', 'activate', 'retire', 'version', 'simulate', 'manage commissions'],
            'historial-crediticio' => ['read'],
            'circulo-credito' => ['create', 'read'],
            'teams' => ['read', 'add members', 'delete', 'remove members', 'update', 'update members', 'create'],
        ];

        foreach ([
            $dashboard,
            $admin,
            $users,
            $roles,
            $menubarItems,
            $activityLog,
            $configuracionEmpresa,
            $sucursales,
            $clientes,
            $productosCrediticios,
            $historial,
            $circuloCredito,
            $teams,
        ] as $module) {
            foreach ($actionsPerModule[$module->name] as $action) {
                Permission::updateOrCreate(
                    [
                        'name' => "{$action} {$module->name}",
                        'guard_name' => 'web',
                    ],
                    [
                        'module_id' => $module->id,
                    ],
                );
            }
        }
    }
}
