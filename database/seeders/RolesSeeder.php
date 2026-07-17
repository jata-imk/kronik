<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $roleModel = config('permission.models.role');

        $roles = [
            'Super Admin',
            'Gerente General',
            'Jefe de Crédito',
            'Oficial de Préstamos',
            'Oficial de Cobranza',
            'Analista de Riesgos',
            'Analista de Crédito',
            'Agente de Atención al Cliente',
            'Analista de Cumplimiento',
            'Supervisor de Operaciones',
            'Recolector de Campo',
            'Capturista de Datos',
            'Asesor Legal',
            'Especialista en Marketing',
            'Administrador de TI',
        ];

        foreach ($roles as $role) {
            $roleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->syncGlobalRolePermissions();
    }

    private function syncGlobalRolePermissions(): void
    {
        $roleModel = config('permission.models.role');
        $teamsKey = config('permission.column_names.team_foreign_key', 'team_id');

        $permissions = Permission::query()->pluck('id', 'name');
        $sets = [
            'Gerente General' => $permissions->keys()->all(),
            'Administrador de TI' => $permissions->keys()->filter(fn ($name) => str_contains($name, 'admin')
                || str_contains($name, 'users')
                || str_contains($name, 'roles')
                || str_contains($name, 'menubar-items')
                || str_contains($name, 'empresa-configuracion')
                || str_contains($name, 'teams')
                || str_contains($name, 'activity-log'))->all(),
            'Jefe de Crédito' => [
                'read dashboard',
                'create clientes',
                'read clientes',
                'update clientes',
                'read historial-crediticio',
                'create circulo-credito',
                'read circulo-credito',
            ],
            'Oficial de Préstamos' => [
                'read dashboard',
                'create clientes',
                'read clientes',
                'update clientes',
                'read historial-crediticio',
                'create circulo-credito',
                'read circulo-credito',
            ],
            'Analista de Crédito' => [
                'read dashboard',
                'read clientes',
                'read historial-crediticio',
                'read circulo-credito',
            ],
            'Analista de Riesgos' => [
                'read dashboard',
                'read clientes',
                'read historial-crediticio',
                'read circulo-credito',
            ],
            'Agente de Atención al Cliente' => [
                'read dashboard',
                'create clientes',
                'read clientes',
                'update clientes',
            ],
            'Supervisor de Operaciones' => [
                'read dashboard',
                'read clientes',
                'update clientes',
                'read historial-crediticio',
            ],
            'Analista de Cumplimiento' => [
                'read dashboard',
                'read clientes',
                'read historial-crediticio',
                'read activity-log',
            ],
        ];

        foreach ($sets as $roleName => $permissionNames) {
            $role = $roleModel::where($teamsKey, null)->where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $role->syncPermissions(
                collect($permissionNames)
                    ->filter(fn ($name) => $permissions->has($name))
                    ->values()
                    ->all()
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
