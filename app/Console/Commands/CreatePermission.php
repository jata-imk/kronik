<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Module;

class CreatePermission extends Command
{
    protected $signature = 'permission:create-permission 
                          {name : The name of the permission}
                          {guard? : The name of the guard}
                          {--module-id= : The module ID for this permission}';

    protected $description = 'Create a permission with optional module assignment';

    public function handle()
    {
        $permissionName = $this->argument('name');
        $guardName = $this->argument('guard') ?: config('auth.defaults.guard');
        $moduleId = $this->option('module-id');

        // Verificar si el permiso ya existe
        if (Permission::where('name', $permissionName)->where('guard_name', $guardName)->exists()) {
            $this->error("Permission '{$permissionName}' already exists for guard '{$guardName}'");
            return 1;
        }

        $permissionData = [
            'name' => $permissionName,
            'guard_name' => $guardName,
        ];

        // Manejar el módulo si se proporciona
        if ($moduleId) {
            $module = $this->findModule($moduleId);

            if (!$module) {
                $this->error("Module '{$moduleId}' not found");
                return 1;
            }

            $permissionData['module_id'] = $module->id;
            $this->info("Assigning permission to module: {$module->name}");
        }

        // Crear el permiso
        $permission = Permission::create($permissionData);

        $this->info("Permission '{$permissionName}' created successfully" .
            ($moduleId ? " for module '{$module->name}'" : ''));

        return 0;
    }

    private function findModule($identifier)
    {
        // Buscar por ID si es numérico, si no devolver null
        if (is_numeric($identifier)) {
            return Module::find($identifier);
        }

        return null;
    }
}
