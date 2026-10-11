<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_version_comisiones', function (Blueprint $t) {
            $t->string('evento_cobro', 30)->nullable();
            $t->string('base_calculo', 30)->change();
        });
        Schema::table('credito_cronogramas', function (Blueprint $t) {
            $t->foreignId('credito_pago_id')->nullable()->constrained('credito_pagos')->restrictOnDelete();
            $t->date('fecha_efectiva')->nullable();
        });
        $module = \App\Models\Module::where('name', 'creditos')->first();
        foreach (['prepay creditos', 'settle creditos'] as $name) {
            \App\Models\Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['module_id' => $module?->id]);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('credito_cronogramas')->whereNotNull('credito_pago_id')->exists()
            || \Illuminate\Support\Facades\DB::table('producto_version_comisiones')->whereNotNull('evento_cobro')->exists()) {
            throw new RuntimeException('No se pueden retirar cronogramas con operaciones conservadas.');
        }
        Schema::table('credito_cronogramas', function (Blueprint $t) {
            $t->dropConstrainedForeignId('credito_pago_id');
            $t->dropColumn('fecha_efectiva');
        });
        Schema::table('producto_version_comisiones', fn (Blueprint $t) => $t->dropColumn('evento_cobro'));
    }
};
