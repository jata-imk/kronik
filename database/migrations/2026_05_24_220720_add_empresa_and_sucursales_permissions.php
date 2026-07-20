<?php

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $configuracion = Module::firstOrCreate(
            ['name' => 'configuracion-empresa'],
            [
                'icon' => 'pi-building',
                'route_name' => 'admin.configuracion-empresa',
                'parent_id' => null,
            ],
        );

        $sucursales = Module::firstOrCreate(
            ['name' => 'sucursales'],
            [
                'icon' => 'pi-map-marker',
                'route_name' => 'admin.sucursales',
                'parent_id' => $configuracion->id,
            ],
        );

        foreach (['read', 'update'] as $action) {
            $permission = Permission::firstOrCreate(
                ['name' => "{$action} configuracion-empresa"],
                ['guard_name' => 'web'],
            );

            $permission->forceFill(['module_id' => $configuracion->id])->save();
        }

        foreach (['create', 'read', 'update', 'delete'] as $action) {
            $permission = Permission::firstOrCreate(
                ['name' => "{$action} sucursales"],
                ['guard_name' => 'web'],
            );

            $permission->forceFill(['module_id' => $sucursales->id])->save();
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'read configuracion-empresa',
            'update configuracion-empresa',
            'create sucursales',
            'read sucursales',
            'update sucursales',
            'delete sucursales',
        ])->delete();

        Module::where('name', 'sucursales')->delete();
        Module::where('name', 'configuracion-empresa')->delete();
    }
};
