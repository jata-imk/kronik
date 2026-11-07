<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_version_reglas', function (Blueprint $table) {
            // Sin backfill: ausencia histórica no equivale a una condición nueva.
            $table->string('base_ordinaria', 30)->nullable();
            $table->string('politica_interes_bullet', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('producto_version_reglas', function (Blueprint $table) {
            $table->dropColumn(['base_ordinaria', 'politica_interes_bullet']);
        });
    }
};
