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
            'parent_id' => null
        ]);

        $circuloCredito = Module::create([
            'name' => 'circulo-credito',
            'icon' => 'pi-list',
            'route_name' => 'circulo-credito',
            'parent_id' => $historial->id
        ]);

        $modules = [
            $clientes,
            $historial,
            $circuloCredito,
        ];

        $actionsPerModule = [
            'clientes' => ['create', 'read', 'update', 'delete'],
            'historial-crediticio' => ['read'],
            'circulo-credito' => ['create', 'read'],
        ];

        foreach ($modules as $module) {
            // Crear permisos para cada módulo
            $actions = $actionsPerModule[$module->name];

            foreach ($actions as $action) {
                $permission = Permission::create([
                    'name' => "{$action} {$module->slug}",
                    'guard_name' => 'web',
                    'module_id' => $module->id, // Si usas Opción 1
                ]);
            }
        }
    }
}
