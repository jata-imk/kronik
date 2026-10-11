<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sic_query_results', fn (Blueprint $table) => $table->json('data')->nullable()->change());
        Schema::table('sic_queries', function (Blueprint $table) {
            $table->string('ambiente', 20)->nullable()->index();
            $table->string('estado_operacion', 30)->nullable()->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->restrictOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('consentimiento_id')->nullable()->constrained('cliente_consentimientos_sic')->restrictOnDelete();
            $table->foreignId('solicitud_revision_id')->nullable()->constrained('solicitud_revisiones')->restrictOnDelete();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->char('payload_hash', 64)->nullable();
            $table->char('identidad_hash', 64)->nullable();
            $table->char('consentimiento_hash', 64)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
        });
        Schema::create('sic_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sic_query_id')->constrained('sic_queries')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->longText('contenido');
            $table->char('contenido_hash', 64);
            $table->timestamps();
            $table->unique(['sic_query_id', 'tipo']);
        });
        Schema::create('sic_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sic_query_id')->constrained('sic_queries')->restrictOnDelete();
            $table->string('estado', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        $module = \App\Models\Module::where('name', 'circulo-credito')->first();
        \App\Models\Permission::firstOrCreate(['name' => 'reconcile circulo-credito', 'guard_name' => 'web'], ['module_id' => $module?->id]);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('sic_evidencias')->exists()) {
            throw new RuntimeException('No se puede retirar una migración con evidencia SIC.');
        }
        Schema::dropIfExists('sic_eventos');
        Schema::dropIfExists('sic_evidencias');
        Schema::table('sic_queries', function (Blueprint $table) {
            foreach (['actor_id', 'team_id', 'sucursal_id', 'consentimiento_id', 'solicitud_revision_id'] as $key) {
                $table->dropConstrainedForeignId($key);
            }
            $table->dropColumn(['ambiente', 'estado_operacion', 'idempotency_key', 'payload_hash', 'identidad_hash', 'consentimiento_hash', 'http_status']);
        });
    }
};
