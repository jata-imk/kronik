<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_resoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->restrictOnDelete();
            $table->foreignId('solicitud_revision_id')->nullable()->constrained('solicitud_revisiones')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('users')->restrictOnDelete();
            $table->string('accion', 24);
            $table->text('motivo');
            $table->timestamps();
            $table->index(['solicitud_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_resoluciones');
    }
};
