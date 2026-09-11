<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_recursos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 160);
            $table->string('disk', 40);
            $table->string('path', 500);
            $table->string('mime_type', 40);
            $table->unsignedInteger('ancho');
            $table->unsignedInteger('alto');
            $table->unsignedInteger('tamano_bytes');
            $table->char('archivo_hash', 64);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::table('documento_plantilla_versiones', function (Blueprint $table) {
            $table->json('presentacion')->nullable();
        });
        Schema::create('documento_version_recursos', function (Blueprint $table) {
            $table->foreignId('version_id')->constrained('documento_plantilla_versiones')->restrictOnDelete();
            $table->foreignUuid('recurso_id')->constrained('documento_recursos')->restrictOnDelete();
            $table->primary(['version_id', 'recurso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_version_recursos');
        Schema::table('documento_plantilla_versiones', fn (Blueprint $table) => $table->dropColumn('presentacion'));
        Schema::dropIfExists('documento_recursos');
    }
};
