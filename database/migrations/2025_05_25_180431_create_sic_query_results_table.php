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
        Schema::create('sic_query_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sic_query_id')->constrained()->onDelete('cascade');
            $table->string('tipo_registro');       // Ej: 'score', 'credito', 'mora'
            $table->json('data');                  // Aquí parseas y guardas partes útiles del JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sic_query_results');
    }
};
