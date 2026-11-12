<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Tests\Support\BulletScenario;

final class E2eBulletSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('e2e') || ! filter_var(env('E2E_DATABASE'), FILTER_VALIDATE_BOOL)
            || (! filter_var(env('E2E_BULLET_FIXTURES'), FILTER_VALIDATE_BOOL) && ! filter_var(env('E2E_HISTORICO_FIXTURES'), FILTER_VALIDATE_BOOL))) {
            throw new \RuntimeException('Bullet E2E requiere preparación explícita en la base aislada.');
        }
        $actor = User::where('email', 'test@example.com')->firstOrFail();
        $historico = filter_var(env('E2E_HISTORICO_FIXTURES'), FILTER_VALIDATE_BOOL);
        if ($historico) {
            $admin = $actor;
            $actor = User::factory()->create(['name' => 'Operador histórico QA', 'email' => 'historico.e2e@example.test',
                'password' => bcrypt('password'), 'is_super_admin' => false,
                'current_team_id' => $admin->current_team_id, 'current_sucursal_id' => $admin->current_sucursal_id,
                'sucursal_principal_id' => $admin->current_sucursal_id]);
            $admin->currentTeam->users()->attach($actor);
            $actor->sucursales()->attach($admin->current_sucursal_id);
            setPermissionsTeamId($actor->current_team_id);
            $actor->givePermissionTo(\App\Models\Permission::where('guard_name', 'web')->get());
        }
        auth()->setUser($actor);
        setPermissionsTeamId($actor->current_team_id);
        $fixtures = [];
        foreach (['periodicos', 'al_vencimiento'] as $politica) {
            $version = BulletScenario::producto($actor, $politica, activar: false);
            if ($historico) {
                $version->update(['dias_gracia_mora' => 3]);
            }
            $cliente = BulletScenario::cliente($actor);
            $fixtures[$politica] = ['producto' => $version->producto_crediticio_id, 'version' => $version->id, 'cliente' => $cliente->id,
                'hoy' => app(\App\Services\FechaEmpresa::class)->hoy()->toDateString(),
                'fecha' => app(\App\Services\FechaEmpresa::class)->hoy()->startOfMonth()->subMonths(2)->toDateString()];
        }
        file_put_contents(storage_path('framework/testing/'.($historico ? 'historico-e2e-fixtures.json' : 'bullet-e2e-fixtures.json')), json_encode($fixtures, JSON_THROW_ON_ERROR));
    }
}
