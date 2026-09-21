<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('creada_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('producto_version_id')->nullable()->constrained('producto_versiones')->restrictOnDelete();
            $table->uuid('clave_creacion');
            $table->char('creacion_hash', 64);
            $table->string('estado', 24)->default('borrador');
            $table->unsignedInteger('lock_version')->default(0);
            $table->decimal('monto', 19, 2)->nullable();
            $table->unsignedSmallInteger('plazo')->nullable();
            $table->string('periodicidad', 20)->nullable();
            $table->string('metodo', 24)->nullable();
            $table->text('destino')->nullable();
            $table->date('fecha_estimada')->nullable();
            $table->timestamp('enviada_en')->nullable();
            $table->timestamps();
            $table->unique(['creada_por', 'clave_creacion']);
            $table->index(['responsable_id', 'estado']);
            $table->index(['sucursal_id', 'estado']);
        });
        Schema::create('solicitud_revisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->foreignId('producto_version_id')->constrained('producto_versiones')->restrictOnDelete();
            $table->foreignId('creada_por')->constrained('users')->restrictOnDelete();
            $table->json('snapshot');
            $table->char('snapshot_hash', 64);
            $table->timestamps();
            $table->unique(['solicitud_id', 'numero']);
        });
        Schema::create('solicitud_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo', 40);
            $table->json('datos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_eventos');
        Schema::dropIfExists('solicitud_revisiones');
        Schema::dropIfExists('solicitudes');
    }
};
