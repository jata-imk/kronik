<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('originacion_politicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_version_id')->constrained('producto_versiones')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->foreignId('creada_por')->constrained('users')->restrictOnDelete();
            $table->json('condiciones');
            $table->string('snapshot_hash', 64);
            $table->timestamps();
            $table->unique(['producto_version_id', 'numero']);
        });
        Schema::table('solicitud_resoluciones', function (Blueprint $table) {
            $table->json('evidencia')->nullable();
            $table->date('vigente_hasta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_resoluciones', function (Blueprint $table) {
            $table->dropColumn(['evidencia', 'vigente_hasta']);
        });
        Schema::dropIfExists('originacion_politicas');
    }
};
