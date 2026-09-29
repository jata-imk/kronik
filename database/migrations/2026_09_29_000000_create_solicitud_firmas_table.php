<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_firmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_paquete_id')->constrained('solicitud_paquetes')->restrictOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('recibida_por')->constrained('users')->restrictOnDelete();
            $table->date('fecha_firma');
            $table->string('disk', 50);
            $table->string('path');
            $table->char('archivo_hash', 64);
            $table->char('original_hash', 64);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('estado', 20)->default('recibida');
            $table->foreignId('revisada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('revisada_en')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();
            $table->index(['solicitud_paquete_id', 'estado']);
        });
        Schema::create('solicitud_formalizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_paquete_id')->unique()->constrained('solicitud_paquetes')->restrictOnDelete();
            $table->foreignId('solicitud_firma_id')->unique()->constrained('solicitud_firmas')->restrictOnDelete();
            $table->foreignId('creada_por')->constrained('users')->restrictOnDelete();
            $table->string('modo', 20)->default('qa');
            $table->longText('snapshot');
            $table->char('snapshot_hash', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('solicitud_firmas')->exists()) {
            throw new RuntimeException('Hay firmas conservadas. Restaura un respaldo coherente; no borres su evidencia.');
        }
        Schema::dropIfExists('solicitud_formalizaciones');
        Schema::dropIfExists('solicitud_firmas');
    }
};
