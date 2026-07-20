<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('singleton_key')->default('default')->unique();
            $table->string('razon_social')->nullable();
            $table->string('nombre_comercial')->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('regimen_fiscal')->nullable();
            $table->json('domicilio_fiscal')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->string('zona_horaria')->default('America/Mexico_City');
            $table->string('pais_base', 2)->default('MX');
            $table->string('logotipo_path')->nullable();
            $table->json('parametros_operativos')->nullable();
            $table->text('integraciones')->nullable();
            $table->string('estatus')->default('borrador');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_configuraciones');
    }
};
