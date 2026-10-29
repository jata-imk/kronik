<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_ambitos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 80)->unique();
            $table->string('tipo', 20)->default('sucursal');
            $table->string('prefijo', 20);
            $table->unsignedBigInteger('version')->default(0);
        });
        Schema::table('sucursales', function (Blueprint $table) {
            $table->foreignId('folio_ambito_id')->nullable()->unique()->constrained('folio_ambitos')->restrictOnDelete();
        });
        Schema::create('folio_secuencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambito_id')->constrained('folio_ambitos')->restrictOnDelete();
            $table->string('tipo', 40);
            $table->unsignedBigInteger('siguiente');
            $table->unique(['ambito_id', 'tipo']);
        });
        Schema::create('folio_prefijos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambito_id')->constrained('folio_ambitos')->restrictOnDelete();
            $table->string('prefijo', 20);
            $table->string('normalizado', 16)->unique();
        });
        Schema::create('folio_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secuencia_id')->constrained('folio_secuencias')->restrictOnDelete();
            $table->unsignedBigInteger('numero');
            $table->string('folio', 50)->unique();
            // Technical uniqueness key, not a second public account number.
            $table->string('normalizado', 50)->virtualAs("replace(folio, '-', '')")->unique();
            $table->string('modelo', 100)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->unsignedBigInteger('sucursal_id');
            $table->boolean('historico')->default(false);
            $table->timestamp('asignado_en');
            $table->unique(['secuencia_id', 'numero']);
            $table->unique(['modelo', 'registro_id']);
        });
        foreach (['solicitudes', 'solicitud_paquetes', 'creditos', 'credito_pagos'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('folio', 50)->nullable()->unique();
                $table->timestamp('folio_asignado_en')->nullable();
                $table->boolean('folio_historico')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (DB::table('folio_asignaciones')->exists()) {
            throw new RuntimeException('No se pueden retirar folios asignados. Restaura una copia consistente si necesitas revertir el despliegue.');
        }
        foreach (['solicitudes', 'solicitud_paquetes', 'creditos', 'credito_pagos'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->dropColumn(['folio', 'folio_asignado_en', 'folio_historico']));
        }
        Schema::table('sucursales', fn (Blueprint $table) => $table->dropConstrainedForeignId('folio_ambito_id'));
        Schema::dropIfExists('folio_asignaciones');
        Schema::dropIfExists('folio_prefijos');
        Schema::dropIfExists('folio_secuencias');
        Schema::dropIfExists('folio_ambitos');
    }
};
