<?php

namespace App\Console\Commands;

use Database\Seeders\DevelopmentSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetDevelopmentData extends Command
{
    protected $signature = 'dev:reset-data {--no-seed : Do not run the development seeder after truncating volatile tables}';

    protected $description = 'Reset development data while preserving heavy SAT/SEPOMEX catalogs';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->confirm('This is a production environment. Continue?')) {
            return self::FAILURE;
        }

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
            'teams',
            'users',
            'sessions',
            'jobs',
            'failed_jobs',
            'job_batches',
        ];

        Schema::disableForeignKeyConstraints();

        try {
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

        if (! $this->option('no-seed')) {
            $this->call('db:seed', [
                '--class' => DevelopmentSeeder::class,
            ]);
        }

        $this->info('Development data reset completed. SAT/SEPOMEX catalogs were preserved.');

        return self::SUCCESS;
    }
}
