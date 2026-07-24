<?php

namespace App\Console\Commands;

use Database\Seeders\DevelopmentSeeder;
use Database\Seeders\SystemSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class ResetDevelopmentData extends Command
{
    protected $signature = 'dev:reset-data {--no-seed : Do not run the development seeder after truncating volatile tables}';

    protected $description = 'Reset development data while preserving heavy SAT/SEPOMEX catalogs';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->confirm('This is a production environment. Continue?')) {
            return self::FAILURE;
        }

        $catalogTables = [
            'paises',
            'regimenes_fiscales',
            'divisiones_administrativas',
            'codigos_postales',
        ];
        $catalogCounts = collect($catalogTables)
            ->filter(fn (string $table) => Schema::hasTable($table))
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()]);

        $tables = [
            config('activitylog.table_name', 'activity_log'),
            'sic_query_results',
            'sic_queries',
            'personal_access_tokens',
            'team_invitations',
            'team_user',
            'direcciones',
            'clientes_datos_fiscales',
            'clientes',
            'sucursales',
            'empresa_configuraciones',
            'teams',
            'users',
            'sessions',
            'jobs',
            'failed_jobs',
            'job_batches',
        ];

        Schema::disableForeignKeyConstraints();

        try {
            $teamsKey = config('permission.column_names.team_foreign_key', 'team_id');
            $teamRoleIds = Schema::hasTable('roles')
                ? DB::table('roles')->whereNotNull($teamsKey)->pluck('id')
                : collect();

            if (Schema::hasTable('role_has_permissions') && $teamRoleIds->isNotEmpty()) {
                DB::table('role_has_permissions')->whereIn('role_id', $teamRoleIds)->delete();
                $this->line('Deleted: team role permissions');
            }

            if (Schema::hasTable('model_has_roles')) {
                DB::table('model_has_roles')->truncate();
                $this->line('Truncated: model_has_roles');
            }

            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')->truncate();
                $this->line('Truncated: model_has_permissions');
            }

            if (Schema::hasTable('roles') && $teamRoleIds->isNotEmpty()) {
                DB::table('roles')->whereIn('id', $teamRoleIds)->delete();
                $this->line('Deleted: team scoped roles');
            }

            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->truncate();
                $this->line("Truncated: {$table}");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (! $this->option('no-seed')) {
            $this->call('db:seed', ['--class' => SystemSeeder::class]);
            $this->call('db:seed', ['--class' => DevelopmentSeeder::class]);
        }

        foreach ($catalogCounts as $table => $count) {
            if (DB::table($table)->count() < $count) {
                $this->error("Catalog integrity check failed: {$table} lost records during reset.");

                return self::FAILURE;
            }
        }

        $this->info('Development data reset completed. SAT/SEPOMEX catalogs were preserved.');

        return self::SUCCESS;
    }
}
