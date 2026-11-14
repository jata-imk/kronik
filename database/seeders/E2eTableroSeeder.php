<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Credito\CapturaHistoricaService;
use App\Services\Credito\DesembolsoService;
use App\Services\Credito\PagoService;
use App\Services\FechaEmpresa;
use App\Services\ProductoVersionService;
use App\Services\SolicitudService;
use App\Support\Decimal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BulletScenario;

/** Cartera y etapas sintéticas por servicios reales, exclusivamente en E2E aislado. */
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
        $inicio = app(FechaEmpresa::class)->hoy()->startOfMonth()->subMonths(2);
        foreach ([true, false] as $alCorriente) {
            $version = BulletScenario::producto($actor, 'periodicos', activar: false);
            $version = app(ProductoVersionService::class)->activar($version, $inicio->toDateString(), true);
            $solicitud = BulletScenario::solicitud($actor, $version);
            $solicitud = app(SolicitudService::class)->guardar($solicitud, ['lock_version' => $solicitud->lock_version,
                'fecha_estimada' => $inicio->toDateString(), 'plazo' => 3], $actor);
            $solicitud = BulletScenario::aprobar($solicitud, $actor);
            $paquete = BulletScenario::formalizar($solicitud, $actor);
            $credito = app(DesembolsoService::class)->registrar($solicitud->fresh(), [...BulletScenario::desembolso($solicitud, $paquete),
                'fecha_desembolso' => $inicio->toDateString(), 'confirmacion_historica' => true], $actor);
            $credito->cliente->update(['primer_nombre' => $alCorriente ? 'Mariana' : 'Rodrigo', 'apellido_paterno' => 'Ejemplo']);
            if ($alCorriente) {
                for ($mes = 1; $mes <= 2; $mes++) {
                    $fecha = $inicio->addMonths($mes);
                    // Oráculo independiente: 12000 × 24 % / 360 = 8 MXN por día.
                    $dias = (int) $inicio->addMonths($mes - 1)->diffInDays($fecha);
                    $datos = ['operacion' => 'pago', 'fecha_pago' => $fecha->toDateString(), 'importe' => Decimal::mul('8', (string) $dias, 2),
                        'referencia' => 'TABLERO-HIST-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
                    $datos['previa_hash'] = app(PagoService::class)->previa($credito, $datos, $actor)['previa_hash'];
                    app(PagoService::class)->registrar($credito, $datos, $actor);
                }
            }
            app(CapturaHistoricaService::class)->completar($credito, $actor);
        }
        foreach (['borrador', 'borrador', 'en_revision', 'aprobada', 'formalizada'] as $etapa) {
            $solicitud = BulletScenario::solicitud($actor, $version);
            if ($etapa === 'en_revision') {
                app(SolicitudService::class)->enviar($solicitud, $solicitud->lock_version, $actor);
            } elseif (in_array($etapa, ['aprobada', 'formalizada'], true)) {
                $solicitud = BulletScenario::aprobar($solicitud, $actor);
                if ($etapa === 'formalizada') {
                    BulletScenario::formalizar($solicitud, $actor);
                }
            }
        }
    }
}
