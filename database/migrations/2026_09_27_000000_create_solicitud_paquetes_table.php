<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_paquetes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->restrictOnDelete();
            $table->foreignId('solicitud_revision_id')->constrained('solicitud_revisiones')->restrictOnDelete();
            $table->foreignId('solicitud_resolucion_id')->unique()->constrained('solicitud_resoluciones')->restrictOnDelete();
            $table->foreignId('documento_plantilla_version_id')->constrained('documento_plantilla_versiones')->restrictOnDelete();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->longText('snapshot');
            $table->char('snapshot_hash', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('solicitud_paquetes')->exists()) {
            throw new RuntimeException('Hay paquetes conservados. Restaura un respaldo coherente de base de datos y archivos; no elimines su trazabilidad.');
        }
        Schema::dropIfExists('solicitud_paquetes');
    }
};
