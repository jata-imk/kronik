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
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('razon_social')->nullable();
            $table->string('nombre_comercial')->nullable();
            $table->string('rfc', 13)->nullable();
            $table->foreignId('regimen_fiscal_id')->nullable()->constrained('regimenes_fiscales')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('domicilio_fiscal')->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->string('zona_horaria')->default('America/Mexico_City');
            $table->json('horario_operacion')->nullable();
            $table->string('folio_credito_prefijo', 20)->nullable();
            $table->unsignedBigInteger('folio_credito_siguiente')->default(1);
            $table->json('dias_inhabiles')->nullable();
            $table->json('reglas_cobranza')->nullable();
            $table->json('formatos_contrato')->nullable();
            $table->json('cuentas_bancarias')->nullable();
            $table->json('contactos')->nullable();
            $table->json('integraciones')->nullable();
            $table->boolean('activa')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_configuraciones');
    }
};
