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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // nombre como 'clientes', 'historial', etc.
            $table->string('icon')->nullable(); // icono base
            $table->string('route_name')->nullable(); // ruta a la cual se accede
            $table->foreignId('parent_id')->nullable()->constrained('modules')->onDelete('cascade'); // id del padre
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
