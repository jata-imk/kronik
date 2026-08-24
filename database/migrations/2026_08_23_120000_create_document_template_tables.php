<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 80)->unique();
            $table->string('nombre', 160);
            $table->string('tipo', 40)->index();
            $table->string('descripcion', 500)->nullable();
            $table->boolean('activa')->default(true);
            $table->foreignId('creada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('documento_plantilla_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_plantilla_id')->constrained('documento_plantillas')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->string('estado', 20)->default('borrador')->index();
            $table->longText('encabezado_html')->nullable();
            $table->longText('contenido_html');
            $table->longText('pie_html')->nullable();
            $table->string('resumen_cambios', 500)->nullable();
            $table->char('contenido_hash', 64);
            $table->timestamp('activada_en')->nullable();
            $table->timestamp('retirada_en')->nullable();
            $table->foreignId('creada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['documento_plantilla_id', 'numero'], 'documento_plantilla_version_numero_unique');
        });

        Schema::create('documentos_generados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('documento_plantilla_version_id')->constrained('documento_plantilla_versiones')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->nullableMorphs('documentable');
            $table->string('estado', 20)->default('pendiente')->index();
            $table->uuid('idempotency_key')->unique();
            $table->longText('datos_utilizados');
            $table->json('metadatos_variables')->nullable();
            $table->string('disk', 40)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('nombre_archivo', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->char('archivo_hash', 64)->nullable();
            $table->string('error_codigo', 80)->nullable();
            $table->string('error_mensaje', 500)->nullable();
            $table->timestamp('solicitado_en');
            $table->timestamp('generado_en')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_generados');
        Schema::dropIfExists('documento_plantilla_versiones');
        Schema::dropIfExists('documento_plantillas');
    }
};
