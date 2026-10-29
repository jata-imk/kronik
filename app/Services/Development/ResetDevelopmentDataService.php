<?php

namespace App\Services\Development;

use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DevelopmentSeeder;
use Database\Seeders\SystemSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class ResetDevelopmentDataService
{
    public const CATALOGS = ['paises', 'regimenes_fiscales', 'divisiones_administrativas', 'codigos_postales'];

    // Child tables precede parents. Foreign keys remain enabled throughout the reset.
    private const TABLES = [
        'credito_cronogramas', 'credito_movimientos', 'credito_pagos', 'credito_desembolsos', 'creditos',
        'solicitud_formalizaciones', 'solicitud_firmas', 'solicitud_paquetes',
        'sic_evidencias', 'sic_eventos', 'sic_query_results', 'sic_queries',
        'solicitud_dictamenes', 'solicitud_resoluciones', 'solicitud_eventos', 'solicitud_revisiones', 'solicitudes',
        'documentos_generados', 'documento_version_recursos', 'documento_recursos',
        'documento_plantilla_versiones', 'documento_plantillas',
        'producto_version_usos', 'originacion_politicas', 'producto_version_comisiones',
        'producto_version_reglas', 'producto_version_periodicidades', 'producto_versiones', 'productos_crediticios',
        'cliente_consentimientos_sic', 'cliente_garantias', 'cliente_vinculos', 'cliente_referencias', 'cliente_documentos',
        'direcciones', 'clientes_datos_fiscales', 'clientes',
        'personal_access_tokens', 'team_invitations', 'team_user', 'sucursal_user',
        'menubar_item_module', 'menubar_items',
        'sessions', 'password_reset_tokens', 'user_invitation_tokens', 'jobs', 'failed_jobs', 'job_batches', 'cache', 'cache_locks',
    ];

    public function __construct(private ResetDataFiles $files) {}

    public function plan(string $mode, ?string $email): array
    {
        $connection = DB::getDefaultConnection();
        if (! in_array(config('session.driver'), ['database', 'array'], true)
            || (config('session.driver') === 'database' && (config('session.table') !== 'sessions'
                || ! in_array(config('session.connection'), [null, $connection], true)))) {
            throw new ResetDataException('El reinicio admite sesiones en la tabla sessions de esta misma base. Revisa la configuración de sesiones antes de continuar.');
        }
        $queue = config('queue.connections.'.config('queue.default'));
        if (! in_array($queue['driver'] ?? null, ['database', 'sync'], true)
            || (($queue['driver'] ?? null) === 'database' && (($queue['table'] ?? null) !== 'jobs'
                || ! in_array($queue['connection'] ?? null, [null, $connection], true)))
            || ! in_array(config('activitylog.database_connection'), [null, $connection], true)) {
            throw new ResetDataException('El reinicio requiere colas y actividad en esta misma base, o cola síncrona. Revisa la configuración antes de continuar.');
        }
        if (! in_array(config('queue.batching.database'), [null, $connection], true)
            || config('queue.batching.table') !== 'job_batches'
            || ! in_array(config('queue.failed.driver'), ['database', 'database-uuids', 'null'], true)
            || (config('queue.failed.driver') !== 'null' && (! in_array(config('queue.failed.database'), [null, $connection], true)
                || config('queue.failed.table') !== 'failed_jobs'))) {
            throw new ResetDataException('Los lotes y trabajos fallidos deben pertenecer a esta misma base para poder reiniciarlos.');
        }
        $tables = [...self::TABLES, config('activitylog.table_name', 'activity_log'),
            config('permission.table_names.model_has_roles'), config('permission.table_names.model_has_permissions')];
        $preserved = [...self::CATALOGS, 'migrations', 'development_data_resets', 'modules',
            'sics', 'sic_apis', 'conceptos_comision', 'folio_ambitos', 'folio_secuencias', 'folio_prefijos', 'folio_asignaciones',
            ...array_values(config('permission.table_names')), 'users', 'teams', 'sucursales', 'empresa_configuraciones'];
        $unknown = array_diff(Schema::getTableListing(), $tables, $preserved);
        if ($unknown) {
            throw new ResetDataException('Hay tablas sin clasificar en el reinicio: '.implode(', ', $unknown));
        }
        foreach ([...$tables, ...$preserved] as $table) {
            if (! Schema::hasTable($table)) {
                throw new ResetDataException('Faltan migraciones requeridas. Ejecuta migrate antes de reiniciar.');
            }
        }
        if (DB::table('development_data_resets')->whereNull('completed_at')->exists()) {
            throw new ResetDataException('Hay una limpieza pendiente. Usa --resume-cleanup con su identificador antes de otro reinicio.');
        }
        foreach (['creditos', 'solicitud_formalizaciones'] as $table) {
            if (DB::table($table)->where(fn ($q) => $q->whereNull('modo')->orWhere('modo', '!=', 'qa'))->exists()) {
                throw new ResetDataException('Se detectaron operaciones fuera de QA. Este comando solo admite datos de prueba.');
            }
        }
        if (DB::table('sic_queries')->whereNotNull('ambiente')->where('ambiente', '!=', 'qa')->exists()) {
            throw new ResetDataException('Se detectaron consultas SIC fuera de QA. No se permite eliminarlas con este comando.');
        }
        $admin = $mode === 'minimal' ? User::where('email', $email)->first() : null;
        if ($mode === 'minimal' && (! $admin || ! $admin->is_super_admin || ! $admin->isActive() || ! $admin->email_verified_at)) {
            throw new ResetDataException('--admin-email debe identificar un superadministrador existente, activo y con correo verificado.');
        }
        $teamId = $admin?->ownedTeams()->orderBy('id')->value('id');
        $branchId = $mode === 'minimal' ? DB::table('sucursales')->where('clave', 'MATRIZ')->value('id') : null;
        $counts = [];
        foreach ([...$tables, 'users', 'teams', 'sucursales', 'empresa_configuraciones'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return ['mode' => $mode, 'admin_id' => $admin?->id, 'team_id' => $teamId, 'branch_id' => $branchId,
            'tables' => $tables, 'counts' => $counts, 'files' => $this->files->collect($admin?->id)];
    }

    public function reset(string $mode, ?string $email): string
    {
        $id = (string) Str::uuid();
        DB::transaction(function () use ($mode, $email, $id) {
            $plan = $this->plan($mode, $email);
            if ($plan['admin_id']) {
                DB::table('users')->where('id', $plan['admin_id'])->lockForUpdate()->first();
            }
            $catalogs = collect(self::CATALOGS)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
            // Break the self reference without bypassing FK enforcement or model immutability in normal flows.
            DB::table('credito_pagos')->whereNotNull('reversa_de')->update(['reversa_de' => null]);
            // Remove nesting before deletion to avoid cascade depth limits on large menus.
            DB::table('menubar_items')->whereNotNull('parent_id')->update(['parent_id' => null]);
            foreach ($plan['tables'] as $table) {
                DB::table($table)->delete();
            }
            $roleTable = config('permission.table_names.roles');
            $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
            $teamRoles = DB::table($roleTable)->whereNotNull($teamKey)->pluck('id');
            DB::table(config('permission.table_names.role_has_permissions'))->whereIn('role_id', $teamRoles)->delete();
            DB::table($roleTable)->whereIn('id', $teamRoles)->delete();
            DB::table('users')->update(['current_team_id' => null, 'sucursal_principal_id' => null,
                'current_sucursal_id' => null, 'remember_token' => null]);
            DB::table('teams')->when($plan['team_id'], fn ($q, $id) => $q->where('id', '!=', $id))->delete();
            DB::table('sucursales')->when($plan['branch_id'], fn ($q, $id) => $q->where('id', '!=', $id))->delete();
            DB::table('users')->when($plan['admin_id'], fn ($q, $id) => $q->where('id', '!=', $id))->delete();

            if ($mode === 'minimal') {
                $this->restoreMinimum($plan);
            } else {
                DB::table('empresa_configuraciones')->delete();
                if ($mode === 'demo') {
                    Model::unguarded(function () {
                        app(SystemSeeder::class)->run();
                        app(DevelopmentSeeder::class)->run();
                    });
                }
            }
            foreach ($catalogs as $table => $count) {
                if (DB::table($table)->count() < $count) {
                    throw new ResetDataException("El catálogo {$table} perdió registros. Se revierte el reinicio.");
                }
            }
            DB::table('development_data_resets')->insert([
                'id' => $id, 'mode' => $mode, 'operator' => getenv('USERNAME') ?: getenv('USER') ?: 'console',
                'preserved_admin_id' => $plan['admin_id'], 'counts' => json_encode($plan['counts'], JSON_THROW_ON_ERROR),
                'file_count' => count($plan['files']),
                'pending_files' => Crypt::encryptString(json_encode($plan['files'], JSON_THROW_ON_ERROR)),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $id;
    }

    private function restoreMinimum(array $plan): void
    {
        $team = $plan['team_id'] ? Team::findOrFail($plan['team_id']) : Team::forceCreate([
            'user_id' => $plan['admin_id'], 'name' => 'Administración', 'personal_team' => false, 'activo' => true,
        ]);
        $team->forceFill(['activo' => true, 'personal_team' => false])->save();
        $branch = $plan['branch_id'] ? Sucursal::findOrFail($plan['branch_id']) : Sucursal::create([
            'nombre' => 'Matriz', 'clave' => 'MATRIZ', 'prefijo_folio' => 'MTZ', 'activa' => true,
        ]);
        $branch->update(['activa' => true]);
        DB::table('users')->where('id', $plan['admin_id'])->update([
            'current_team_id' => $team->id, 'current_sucursal_id' => $branch->id, 'sucursal_principal_id' => $branch->id,
        ]);
        DB::table('sucursal_user')->insert(['user_id' => $plan['admin_id'], 'sucursal_id' => $branch->id,
            'created_at' => now(), 'updated_at' => now()]);
        $roleModel = config('permission.models.role');
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
        foreach ($roleModel::whereNull($teamKey)->where('name', '!=', 'Super Admin')->with('permissions')->get() as $role) {
            $copy = $roleModel::query()->create(['name' => $role->name, 'guard_name' => $role->guard_name, $teamKey => $team->id]);
            $copy->syncPermissions($role->permissions);
        }
    }

    public function cleanup(string $id): int
    {
        $run = DB::table('development_data_resets')->where('id', $id)->first();
        if (! $run) {
            throw new ResetDataException('No existe el registro de reinicio indicado.');
        }
        $pending = json_decode(Crypt::decryptString($run->pending_files), true, flags: JSON_THROW_ON_ERROR);
        $remaining = [];
        foreach ($pending as $file) {
            try {
                if (! $this->files->delete($file)) {
                    $remaining[] = $file;
                }
            } catch (Throwable) {
                $remaining[] = $file;
            }
        }
        DB::table('development_data_resets')->where('id', $id)->update([
            'pending_files' => Crypt::encryptString(json_encode($remaining, JSON_THROW_ON_ERROR)),
            'completed_at' => $remaining ? null : ($run->completed_at ?? now()), 'updated_at' => now(),
        ]);

        return count($remaining);
    }
}
