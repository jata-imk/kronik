<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conceptos_comision', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('referencia_reco')->nullable();
            $table->boolean('es_oficial_reco')->default(false);
            $table->boolean('revisado')->default(false);
            $table->boolean('activo')->default(true);
            $table->date('vigente_desde')->nullable();
            $table->date('retirado_desde')->nullable();
            $table->timestamps();
        });

        Schema::create('productos_crediticios', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 40)->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('tipo', 30)->default('simple');
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('producto_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_crediticio_id')->constrained('productos_crediticios')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->string('estado', 20)->default('borrador')->index();
            $table->char('moneda', 3)->default('MXN');
            $table->decimal('monto_minimo', 19, 4);
            $table->decimal('monto_maximo', 19, 4);
            $table->decimal('tasa_ordinaria_anual', 12, 8);
            $table->decimal('tasa_moratoria_anual', 12, 8)->default(0);
            $table->unsignedSmallInteger('dias_gracia_mora')->default(0);
            $table->boolean('cat_aplica')->default(true);
            $table->string('cat_no_aplica_motivo')->nullable();
            $table->date('vigente_desde')->nullable();
            $table->timestamp('activada_en')->nullable();
            $table->timestamp('retirada_en')->nullable();
            $table->json('snapshot')->nullable();
            $table->char('snapshot_hash', 64)->nullable();
            $table->foreignId('creada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['producto_crediticio_id', 'numero']);
            $table->unique(['producto_crediticio_id', 'vigente_desde']);
        });

        Schema::create('producto_version_periodicidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_version_id')->constrained('producto_versiones')->cascadeOnDelete();
            $table->string('periodicidad', 20);
            $table->unsignedSmallInteger('plazo_minimo');
            $table->unsignedSmallInteger('plazo_maximo');
            $table->unsignedSmallInteger('plazo_predeterminado');
            $table->timestamps();
            $table->unique(['producto_version_id', 'periodicidad'], 'producto_version_periodicidad_unique');
        });

        Schema::create('producto_version_reglas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_version_id')->unique()->constrained('producto_versiones')->cascadeOnDelete();
            $table->json('metodos_amortizacion');
            $table->string('convencion_interes', 30)->default('dias_reales_360');
            $table->string('base_moratoria', 30)->default('capital_vencido');
            $table->boolean('permite_prepago_parcial')->default(true);
            $table->boolean('permite_liquidacion_anticipada')->default(true);
            $table->decimal('monto_minimo_prepago', 19, 4)->nullable();
            $table->string('aplicacion_prepago', 20)->default('reducir_plazo');
            $table->string('ajuste_dia_inhabil', 20)->default('sin_ajuste');
            $table->string('redondeo', 20)->default('half_up');
            $table->timestamps();
        });

        Schema::create('producto_version_comisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_version_id')->constrained('producto_versiones')->cascadeOnDelete();
            $table->foreignId('concepto_comision_id')->constrained('conceptos_comision')->restrictOnDelete();
            $table->string('tipo_importe', 20);
            $table->decimal('importe', 19, 8);
            $table->string('base_calculo', 30)->default('no_aplica');
            $table->string('momento_cobro', 30);
            $table->boolean('obligatoria')->default(true);
            $table->boolean('incluye_cat')->default(true);
            $table->timestamps();
            $table->unique(['producto_version_id', 'concepto_comision_id'], 'producto_version_comision_unique');
        });

        Schema::create('producto_version_usos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_version_id')->constrained('producto_versiones')->restrictOnDelete();
            $table->string('usable_type');
            $table->unsignedBigInteger('usable_id');
            $table->char('snapshot_hash', 64);
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['usable_type', 'usable_id']);
            $table->index(['producto_version_id', 'created_at']);
        });

        if (Schema::hasTable('modules') && Schema::hasTable('permissions')) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => 'productos-crediticios', 'icon' => 'pi-wallet',
                'route_name' => 'productos-crediticios.index', 'parent_id' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (['create', 'read', 'update', 'activate', 'retire', 'version', 'simulate', 'manage commissions'] as $action) {
                DB::table('permissions')->insert([
                    'name' => "$action productos-crediticios", 'guard_name' => 'web',
                    'module_id' => $moduleId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('name', 'like', '% productos-crediticios')->delete();
        }
        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('name', 'productos-crediticios')->delete();
        }
        Schema::dropIfExists('producto_version_usos');
        Schema::dropIfExists('producto_version_comisiones');
        Schema::dropIfExists('producto_version_reglas');
        Schema::dropIfExists('producto_version_periodicidades');
        Schema::dropIfExists('producto_versiones');
        Schema::dropIfExists('productos_crediticios');
        Schema::dropIfExists('conceptos_comision');
    }
};
