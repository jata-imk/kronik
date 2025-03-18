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
        Schema::create('clientes_datos_fiscales', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cliente_id');
            
            $table->rawColumn('tipo_persona', 'varchar(7) not null constraint tipo_persona_check check (tipo_persona in (\'fisica\', \'moral\'))');

            $table->unsignedBigInteger('regimen_fiscal_id');
            $table->string('curp');
            $table->string('rfc');
            $table->string('razon_social');

            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('regimen_fiscal_id')->references('id')->on('regimenes_fiscales');
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
