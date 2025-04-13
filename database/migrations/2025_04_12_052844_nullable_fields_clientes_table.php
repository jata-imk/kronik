<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('segundo_nombre', 127)->nullable()->change();
            $table->string('apellido_materno', 127)->nullable()->change();
            $table->string('telefono_codigo_pais', 4)->nullable()->change();
        });

        Schema::table('clientes_datos_fiscales', function (Blueprint $table) {
            $table->unsignedBigInteger('regimen_fiscal_id')->nullable()->change();
            $table->string('razon_social')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('segundo_nombre', 127)->change();
            $table->string('apellido_materno', 127)->change();
            $table->string('telefono_codigo_pais', 4)->change();
        });

        Schema::table('clientes_datos_fiscales', function (Blueprint $table) {
            $table->unsignedBigInteger('regimen_fiscal_id');
            $table->string('razon_social');
        });
    }
};
