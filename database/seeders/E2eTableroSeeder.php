<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Credito\DesembolsoService;
use App\Services\Credito\PagoService;
use App\Services\FechaEmpresa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BulletScenario;

/** Dos créditos sintéticos por servicios reales, exclusivamente en E2E aislado. */
class E2eTableroSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('e2e') || ! filter_var(env('E2E_DATABASE'), FILTER_VALIDATE_BOOL)
            || DB::connection()->getDriverName() !== 'sqlite'
            || realpath(DB::connection()->getDatabaseName()) !== realpath(storage_path('framework/testing/kronik-e2e.sqlite'))) {
            throw new \RuntimeException('El fixture del tablero solo admite la SQLite E2E aislada.');
        }
        BulletScenario::habilitar();
        BulletScenario::pdfSintetico();
        $actor = User::where('email', 'test@example.com')->firstOrFail();
        auth()->setUser($actor);
        setPermissionsTeamId($actor->current_team_id);
        foreach (['periodicos', 'al_vencimiento'] as $politica) {
            $version = BulletScenario::producto($actor, $politica);
            $solicitud = BulletScenario::aprobar(BulletScenario::solicitud($actor, $version), $actor);
            $paquete = BulletScenario::formalizar($solicitud, $actor);
            $credito = app(DesembolsoService::class)->registrar($solicitud->fresh(), BulletScenario::desembolso($solicitud, $paquete), $actor);
            if ($politica === 'periodicos') {
                $datos = ['operacion' => 'prepago', 'fecha_pago' => app(FechaEmpresa::class)->hoy()->toDateString(), 'importe' => '3120.00',
                    'referencia' => 'TABLERO-E2E-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
                $datos['previa_hash'] = app(PagoService::class)->previa($credito, $datos, $actor)['previa_hash'];
                app(PagoService::class)->registrar($credito, $datos, $actor);
            }
        }
    }
}
