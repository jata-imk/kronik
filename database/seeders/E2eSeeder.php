<?php

namespace Database\Seeders;

use App\Enums\ActivityEvent;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\ProductoVersionService;
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

            $producto = app(ProductoVersionService::class)->crear([
                'clave' => 'CS-ESENCIAL',
                'nombre' => 'Crédito Simple Esencial',
                'descripcion' => 'Financiamiento flexible para necesidades personales y de negocio.',
                'version' => [
                    'monto_minimo' => '5000', 'monto_maximo' => '150000',
                    'tasa_ordinaria_anual' => '36', 'tasa_moratoria_anual' => '72',
                    'dias_gracia_mora' => 3, 'cat_aplica' => true, 'cat_no_aplica_motivo' => null,
                    'vigente_desde' => null,
                    'periodicidades' => [
                        ['periodicidad' => 'quincenal', 'plazo_minimo' => 6, 'plazo_maximo' => 48, 'plazo_predeterminado' => 24],
                        ['periodicidad' => 'mensual', 'plazo_minimo' => 3, 'plazo_maximo' => 24, 'plazo_predeterminado' => 12],
                    ],
                    'reglas' => [
                        'metodos_amortizacion' => ['cuota_nivelada', 'capital_fijo'],
                        'permite_prepago_parcial' => true, 'permite_liquidacion_anticipada' => true,
                        'monto_minimo_prepago' => '500', 'aplicacion_prepago' => 'reducir_plazo',
                    ],
                    'comisiones' => [],
                ],
            ], $admin->id);
            app(ProductoVersionService::class)->activar($producto->versiones()->first(), today()->toDateString());
        });
    }
}
