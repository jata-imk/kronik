<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
    }
}
