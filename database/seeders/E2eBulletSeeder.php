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
            || ! filter_var(env('E2E_BULLET_FIXTURES'), FILTER_VALIDATE_BOOL)) {
            throw new \RuntimeException('Bullet E2E requiere preparación explícita en la base aislada.');
        }
        $actor = User::where('email', 'test@example.com')->firstOrFail();
        auth()->setUser($actor);
        setPermissionsTeamId($actor->current_team_id);
        $fixtures = [];
        foreach (['periodicos', 'al_vencimiento'] as $politica) {
            $version = BulletScenario::producto($actor, $politica, activar: false);
            $cliente = BulletScenario::cliente($actor);
            $fixtures[$politica] = ['producto' => $version->producto_crediticio_id, 'version' => $version->id, 'cliente' => $cliente->id];
        }
        file_put_contents(storage_path('framework/testing/bullet-e2e-fixtures.json'), json_encode($fixtures, JSON_THROW_ON_ERROR));
    }
}
