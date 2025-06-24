<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;

class ModulesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Crear módulos
        $clientes = Module::create([
            'name' => 'clientes',
            'icon' => 'pi-users',
            'route_name' => 'clientes',
            'parent_id' => null
        ]);

        $historial = Module::create([
            'name' => 'historial-crediticio',
            'icon' => 'pi-list',
            'route_name' => 'historial-crediticio',
            'parent_id' => $clientes->id
        ]);

        $circuloCredito = Module::create([
            'name' => 'circulo-credito',
            'icon' => 'pi-list',
            'route_name' => 'circulo-credito',
            'parent_id' => $historial->id
        ]);

        $teams = Module::create([
            'name' => 'teams',
            'icon' => 'pi-sitemap',
            'route_name' => 'teams',
            'parent_id' => null
        ]);

        $modules = [
            $clientes,
            $historial,
            $circuloCredito,
            $teams
        ];

        $actionsPerModule = [
            'clientes' => ['create', 'read', 'update', 'delete'],
            'historial-crediticio' => ['read'],
            'circulo-credito' => ['create', 'read'],
            'teams' => ['add members', 'delete', 'remove members', 'update', 'update members', 'create'],
        ];

        foreach ($modules as $module) {
            // Crear permisos para cada módulo
            $actions = $actionsPerModule[$module->name];

            foreach ($actions as $action) {
                Permission::create([
                    'name' => "{$action} {$module->name}",
                    'guard_name' => 'web',
                    'module_id' => $module->id, // Si usas Opción 1
                ]);
            }
        }
    }
}
