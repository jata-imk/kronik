<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credito_capturas_historicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->unique()->constrained('creditos')->restrictOnDelete();
            $table->foreignId('iniciada_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('completada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('completada_team_id')->nullable()->constrained('teams')->restrictOnDelete();
            $table->timestamp('completada_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('credito_capturas_historicas')->exists()) {
            throw new RuntimeException('No se puede eliminar evidencia de capturas históricas registradas.');
        }
        Schema::dropIfExists('credito_capturas_historicas');
    }
};
