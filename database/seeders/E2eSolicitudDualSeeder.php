<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\RegimenFiscal;
use App\Models\User;
use App\Services\FechaEmpresa;
use App\Services\OriginacionPoliticaService;
use App\Services\SolicitudService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class E2eSolicitudDualSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('e2e') || ! filter_var(env('E2E_DATABASE'), FILTER_VALIDATE_BOOL)
            || ! filter_var(env('E2E_ORIGINACION_DUAL'), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('El escenario dual solo puede prepararse en E2E aislado y explícito.');
        }
        $admin = User::where('email', 'test@example.com')->firstOrFail();
        setPermissionsTeamId($admin->current_team_id);
        $usuarios = [];
        foreach (['captura', 'aprobacion'] as $perfil) {
            $user = User::factory()->create([
                'name' => ucfirst($perfil).' Dual E2E', 'email' => $perfil.'.dual@example.test',
                'password' => bcrypt('password'), 'is_super_admin' => false,
                'current_team_id' => $admin->current_team_id, 'status' => UserStatus::Active,
                'current_sucursal_id' => $admin->current_sucursal_id,
                'sucursal_principal_id' => $admin->current_sucursal_id,
            ]);
            $admin->currentTeam->users()->attach($user->id);
            $user->sucursales()->attach($admin->current_sucursal_id);
            $user->givePermissionTo(['read clientes', 'read solicitudes', 'create solicitudes', 'update solicitudes', 'approve solicitudes']);
            $usuarios[$perfil] = $user;
        }
        $cliente = Cliente::factory()->create(['primer_nombre' => 'Escenario', 'apellido_paterno' => 'Dual', 'sucursal_id' => $admin->current_sucursal_id]);
        $cliente->datosFiscales()->create(['tipo_persona' => 'fisica', 'regimen_fiscal_id' => RegimenFiscal::where('fisica', true)->firstOrFail()->id,
            'curp' => 'GODE561231HDFRRN09', 'rfc' => 'GODE561231GR8', 'razon_social' => 'Escenario dual sintético']);
        // Isolated test disk: no writing to the installation's document store.
        Storage::disk('e2e_dual')->put('ine.txt', 'Evidencia sintética, sin valor jurídico.');
        $cliente->documentos()->create(['tipo' => 'ine', 'version' => 1, 'estado' => 'validado', 'es_actual' => true,
            'disk' => 'e2e_dual', 'path' => 'ine.txt', 'mime_type' => 'text/plain', 'nombre_original' => 'ine.txt',
            'tamano_bytes' => 40, 'revisado_en' => now(), 'revisado_por' => $admin->id]);
        $producto = ProductoVersion::whereHas('producto', fn ($q) => $q->where('clave', 'CS-ESENCIAL'))->firstOrFail();
        app(OriginacionPoliticaService::class)->crear($producto, ['version_anterior' => 0, 'modalidad' => 'dual', 'sic' => 'manual_permitido',
            'monto_maximo' => '50000.00', 'vigencia_dias' => 15, 'documentos' => ['ine'], 'confirmacion' => true,
            'criterio_capacidad' => 'Revisar ingresos y egresos ficticios del escenario dual E2E.',
            'referencia_validacion' => 'Prueba automática aislada; no configura operación real.'], $admin);
        $service = app(SolicitudService::class);
        $solicitud = $service->crear(['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid(),
            'producto_version_id' => $producto->id, 'monto' => '10000.00', 'plazo' => 12, 'periodicidad' => 'mensual',
            'metodo' => 'cuota_nivelada', 'destino' => 'Escenario dual para pruebas de navegador.',
            'fecha_estimada' => app(FechaEmpresa::class)->hoy()->addWeek()->toDateString()], $usuarios['captura']);
        $service->enviar($solicitud, 0, $usuarios['captura']);
        foreach (['evaluacion', 'pld'] as $index => $tipo) {
            $service->dictaminar($solicitud, ['lock_version' => $index + 1, 'tipo_dictamen' => $tipo,
                'resultado' => $tipo === 'pld' ? 'sin_observaciones' : 'favorable',
                ...($tipo === 'pld' ? ['nivel_riesgo' => 'bajo'] : []),
                'metodologia' => 'Manual sintético E2E versión 1', 'fuentes' => 'Expediente ficticio del escenario dual.',
                'fundamento' => 'Evidencia revisada solo para probar separación de funciones.'], $admin);
        }
    }
}
