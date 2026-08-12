<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['sucursal_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sucursal_principal_id')->nullable()->after('current_team_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('current_sucursal_id')->nullable()->after('sucursal_principal_id')->constrained('sucursales')->restrictOnDelete();
            $table->string('status', 20)->default('active')->after('current_sucursal_id')->index();
            $table->boolean('is_super_admin')->default(false)->after('status')->index();
            $table->timestamp('invited_at')->nullable()->after('is_super_admin');
            $table->timestamp('activated_at')->nullable()->after('invited_at');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('personal_team')->index();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('id')->constrained('sucursales')->restrictOnDelete();
            $table->index(['sucursal_id', 'created_at']);
        });

        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('team_id')->constrained('sucursales')->nullOnDelete();
            $table->index(['sucursal_id', 'created_at']);
        });

        $this->backfillExistingData();
        $this->ensurePermissions();
    }

    private function ensurePermissions(): void
    {
        $now = now();
        foreach ([
            ['module' => 'teams', 'permission' => 'read teams'],
            ['module' => 'clientes', 'permission' => 'transfer clientes'],
        ] as $definition) {
            $moduleId = DB::table('modules')->where('name', $definition['module'])->value('id');
            if (! $moduleId || DB::table('permissions')->where('name', $definition['permission'])->where('guard_name', 'web')->exists()) {
                continue;
            }

            DB::table('permissions')->insert([
                'name' => $definition['permission'],
                'guard_name' => 'web',
                'module_id' => $moduleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillExistingData(): void
    {
        $hasUsers = DB::table('users')->exists();
        $hasClientes = DB::table('clientes')->exists();

        if ($hasUsers || $hasClientes) {
            $matriz = DB::table('sucursales')
                ->where('clave', 'MATRIZ')
                ->where('activa', true)
                ->first();

            if (! $matriz) {
                throw new \RuntimeException('No se puede migrar usuarios y clientes: falta una sucursal MATRIZ activa.');
            }

            $now = now();
            $userIds = DB::table('users')->pluck('id');

            foreach ($userIds as $userId) {
                DB::table('sucursal_user')->insertOrIgnore([
                    'sucursal_id' => $matriz->id,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('users')->update([
                'sucursal_principal_id' => $matriz->id,
                'current_sucursal_id' => $matriz->id,
                'activated_at' => DB::raw('COALESCE(activated_at, created_at)'),
            ]);
            DB::table('clientes')->whereNull('sucursal_id')->update(['sucursal_id' => $matriz->id]);
            DB::connection(config('activitylog.database_connection'))
                ->table(config('activitylog.table_name'))
                ->whereNull('sucursal_id')
                ->update(['sucursal_id' => $matriz->id]);
        }

        $superAdminRoleIds = DB::table('roles')->where('name', 'Super Admin')->pluck('id');
        if ($superAdminRoleIds->isNotEmpty()) {
            $superAdminIds = DB::table('model_has_roles')
                ->whereIn('role_id', $superAdminRoleIds)
                ->where('model_type', (new User)->getMorphClass())
                ->pluck('model_id');

            DB::table('users')->whereIn('id', $superAdminIds)->update(['is_super_admin' => true]);
        }

        DB::table('teams')->where('personal_team', true)->update(['personal_team' => false]);
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', ['read teams', 'transfer clientes'])->delete();
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropIndex(['sucursal_id', 'created_at']);
            $table->dropConstrainedForeignId('sucursal_id');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['sucursal_id', 'created_at']);
            $table->dropConstrainedForeignId('sucursal_id');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_sucursal_id');
            $table->dropConstrainedForeignId('sucursal_principal_id');
            $table->dropColumn(['status', 'is_super_admin', 'invited_at', 'activated_at']);
        });

        Schema::dropIfExists('sucursal_user');
    }
};
