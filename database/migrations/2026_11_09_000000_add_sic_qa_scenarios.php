<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sic_queries', function (Blueprint $table) {
            $table->string('escenario_clave', 100)->nullable();
            $table->unsignedInteger('escenario_version')->nullable();
            $table->string('procedencia', 30)->nullable();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('sic_queries')->whereNotNull('escenario_clave')->exists()) {
            throw new RuntimeException('No se puede retirar la procedencia de intentos SIC existentes.');
        }
        Schema::table('sic_queries', fn (Blueprint $table) => $table->dropColumn(['escenario_clave', 'escenario_version', 'procedencia']));
    }
};
