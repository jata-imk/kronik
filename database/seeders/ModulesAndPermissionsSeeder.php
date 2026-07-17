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
            ]
        );

        $admin = Module::updateOrCreate(
            ['name' => 'admin'],
            [
                'icon' => 'pi-shield',
                'route_name' => 'admin.dashboard',
                'parent_id' => null,
            ]
        );

        $users = Module::updateOrCreate(
            ['name' => 'users'],
            [
                'icon' => 'pi-user',
                'route_name' => 'admin.users',
                'parent_id' => $admin->id,
            ]
        );

        $roles = Module::updateOrCreate(
            ['name' => 'roles'],
            [
                'icon' => 'pi-key',
                'route_name' => 'admin.roles',
                'parent_id' => $admin->id,
            ]
        );

        $menubarItems = Module::updateOrCreate(
            ['name' => 'menubar-items'],
            [
                'icon' => 'pi-bars',
                'route_name' => 'admin.menubar-items',
                'parent_id' => $admin->id,
            ]
        );

        $activityLog = Module::updateOrCreate(
            ['name' => 'activity-log'],
            [
                'icon' => 'pi-history',
                'route_name' => 'admin.users.activity',
                'parent_id' => $admin->id,
            ]
        );

        $empresaConfiguracion = Module::updateOrCreate(
            ['name' => 'empresa-configuracion'],
            [
                'icon' => 'pi-building',
                'route_name' => 'admin.empresa-configuracion',
                'parent_id' => $admin->id,
            ]
        );

        $clientes = Module::updateOrCreate(
            ['name' => 'clientes'],
            [
                'icon' => 'pi-users',
                'route_name' => 'clientes',
                'parent_id' => null,
            ]
        );

        $historial = Module::updateOrCreate(
            ['name' => 'historial-crediticio'],
            [
                'icon' => 'pi-list',
                'route_name' => 'historial-crediticio',
                'parent_id' => $clientes->id,
            ]
        );

        $circuloCredito = Module::updateOrCreate(
            ['name' => 'circulo-credito'],
            [
                'icon' => 'pi-list',
                'route_name' => 'circulo-credito',
                'parent_id' => $historial->id,
            ]
        );

        $teams = Module::updateOrCreate(
            ['name' => 'teams'],
            [
                'icon' => 'pi-sitemap',
                'route_name' => 'teams',
                'parent_id' => null,
            ]
        );

        $modules = [
            $dashboard,
            $admin,
            $users,
            $roles,
            $menubarItems,
            $activityLog,
            $empresaConfiguracion,
            $clientes,
            $historial,
            $circuloCredito,
            $teams,
        ];

        $actionsPerModule = [
            'dashboard' => ['read'],
            'admin' => ['access'],
            'users' => ['create', 'read', 'update', 'delete'],
            'roles' => ['create', 'read', 'update', 'delete'],
            'menubar-items' => ['create', 'read', 'update', 'delete'],
            'activity-log' => ['read'],
            'empresa-configuracion' => ['read', 'update'],
            'clientes' => ['create', 'read', 'update', 'delete'],
            'historial-crediticio' => ['read'],
            'circulo-credito' => ['create', 'read'],
            'teams' => ['add members', 'delete', 'remove members', 'update', 'update members', 'create'],
        ];

        foreach ($modules as $module) {
            // Crear permisos para cada módulo
            $actions = $actionsPerModule[$module->name];

            foreach ($actions as $action) {
                Permission::updateOrCreate(
                    [
                        'name' => "{$action} {$module->name}",
                        'guard_name' => 'web',
                    ],
                    [
                        'module_id' => $module->id,
                    ]
                );
            }
        }
    }
}
