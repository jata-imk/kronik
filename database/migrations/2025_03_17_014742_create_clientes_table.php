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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('primer_nombre', 127);
            $table->string('segundo_nombre', 127);
            $table->string('apellido_paterno', 127);
            $table->string('apellido_materno', 127);

            $table->date('fecha_nacimiento');
            $table->bigInteger('pais_nacimiento_id');
            
            $table->string('telefono_codigo_pais', 4);
            $table->string('telefono', 15);
            $table->string('email', 127);
            
            $table->rawColumn('sexo', 'varchar(15) not null constraint sexo_check check (sexo in (\'masculino\', \'femenino\'))');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
