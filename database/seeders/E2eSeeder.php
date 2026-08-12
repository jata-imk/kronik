<?php

namespace Database\Seeders;

use App\Enums\ActivityEvent;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('e2e') || ! filter_var(env('E2E_DATABASE'), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('E2eSeeder solo puede ejecutarse en el entorno e2e aislado.');
        }

        $this->call(SystemSeeder::class);
        $this->call(DevelopmentSeeder::class);

        DB::transaction(function (): void {
            $admin = User::where('email', 'test@example.com')->firstOrFail();
            $secondBranch = Sucursal::updateOrCreate(
                ['clave' => 'NORTE'],
                [
                    'nombre' => 'Sucursal Norte',
                    'prefijo_folio' => 'NTE',
                    'consecutivo_solicitud' => 1,
                    'consecutivo_contrato' => 1,
                    'consecutivo_credito' => 1,
                    'consecutivo_recibo' => 1,
                    'activa' => true,
                ],
            );
            Sucursal::updateOrCreate(
                ['clave' => 'ARCHIVO'],
                [
                    'nombre' => 'Sucursal Archivada',
                    'prefijo_folio' => 'ARC',
                    'consecutivo_solicitud' => 1,
                    'consecutivo_contrato' => 1,
                    'consecutivo_credito' => 1,
                    'consecutivo_recibo' => 1,
                    'activa' => false,
                ],
            );

            $admin->sucursales()->syncWithoutDetaching([$secondBranch->id]);

            Team::updateOrCreate(
                ['user_id' => $admin->id, 'name' => 'Operaciones E2E'],
                ['personal_team' => false, 'activo' => true],
            );

            app(ActivityLogService::class)->log(
                event: ActivityEvent::Login,
                description: 'Actividad determinista E2E',
                causer: $admin,
            );
        });
    }
}
