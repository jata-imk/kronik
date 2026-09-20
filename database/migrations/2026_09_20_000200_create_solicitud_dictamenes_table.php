<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_dictamenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->restrictOnDelete();
            $table->foreignId('solicitud_revision_id')->constrained('solicitud_revisiones')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo', 24);
            $table->string('resultado', 32);
            $table->text('contenido');
            $table->timestamps();
            $table->index(['solicitud_revision_id', 'tipo', 'id'], 'dictamen_revision_tipo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_dictamenes');
    }
};
