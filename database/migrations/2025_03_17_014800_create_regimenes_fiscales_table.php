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
        Schema::create('regimenes_fiscales', function (Blueprint $table) {
            $table->id();

            $table->string('clave', 3);
            $table->string('descripcion', 127);
            $table->boolean('fisica');
            $table->boolean('moral');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regimenes_fiscales');
    }
};
