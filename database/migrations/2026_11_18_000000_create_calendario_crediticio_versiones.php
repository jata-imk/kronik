<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendario_crediticio_versiones', function (Blueprint $table) {
            $table->id();
            $table->json('configuracion');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('publicada_en')->nullable();
            $table->unsignedBigInteger('orden_publicacion')->nullable()->unique();
            $table->string('huella', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_crediticio_versiones');
    }
};
