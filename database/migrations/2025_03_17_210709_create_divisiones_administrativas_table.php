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
        Schema::create('divisiones_administrativas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pais_id')->notNullable();
            $table->string('nombre', 255)->notNullable();
            $table->string('codigo', 50)->nullable()->comment('Código oficial si existe');
            $table->tinyInteger('nivel')->notNullable()->comment('1=primer nivel (estado/provincia), 2=segundo nivel (municipio/cantón), 3=tercer nivel (localidad/parroquia)');
            $table->unsignedBigInteger('division_padre_id')->nullable()->comment('Referencia a la división administrativa superior');
            $table->string('tipo', 50)->nullable()->comment('Tipo de división: estado, provincia, municipio, cantón, etc.');

            $table->foreign('pais_id')->references('id')->on('paises');
            $table->foreign('division_padre_id')->references('id')->on('divisiones_administrativas');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('divisiones_administrativas');
    }
};
