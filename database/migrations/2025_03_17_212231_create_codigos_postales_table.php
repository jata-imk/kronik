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
        Schema::create('codigos_postales', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 15)->notNullable();
            $table->unsignedBigInteger('pais_id')->notNullable();
            $table->unsignedBigInteger('division_admin_id')->nullable()->comment('División administrativa a la que pertenece principalmente');
            $table->json('datos_adicionales')->nullable()->comment('Información adicional específica del país');
            
            $table->foreign('pais_id')->references('id')->on('paises');
            $table->foreign('division_admin_id')->references('id')->on('divisiones_administrativas');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos_postales');
    }
};
