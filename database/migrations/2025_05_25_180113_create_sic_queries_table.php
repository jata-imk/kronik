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
        Schema::create('sic_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade'); // Asegúrate de tener clientes
            $table->foreignId('sic_id')->constrained()->onDelete('cascade');
            $table->foreignId('sic_api_id')->constrained()->onDelete('cascade');
            $table->dateTime('fecha_consulta');
            $table->enum('status', ['success', 'error', 'pending'])->default('pending');
            $table->text('mensaje_error')->nullable();
            $table->json('response_data')->nullable(); // Guarda la respuesta cruda del API
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sic_queries');
    }
};
