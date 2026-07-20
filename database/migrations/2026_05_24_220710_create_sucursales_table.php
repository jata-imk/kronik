<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('clave', 20)->unique();
            $table->json('domicilio')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->json('horario')->nullable();
            $table->string('prefijo_folio', 20)->nullable();
            $table->unsignedBigInteger('consecutivo_solicitud')->default(1);
            $table->unsignedBigInteger('consecutivo_contrato')->default(1);
            $table->unsignedBigInteger('consecutivo_credito')->default(1);
            $table->unsignedBigInteger('consecutivo_recibo')->default(1);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
