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
        Schema::create('sic_apis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sic_id')->constrained()->onDelete('cascade');
            $table->string('nombre');       // Ej: 'Consulta Completa'
            $table->string('clave');        // Ej: 'completa'
            $table->string('endpoint_url')->nullable(); // Por si quieres tenerlo referenciado
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['sic_id', 'clave']); // Previene duplicados dentro del mismo SIC
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sic_apis');
    }
};
