<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_versiones', function (Blueprint $table) {
            // No backfill: historical contracts did not select these rules.
            $table->json('politica_mora')->nullable();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('producto_versiones')->whereNotNull('politica_mora')->exists()) {
            throw new RuntimeException('No puede retirarse la política de mora mientras existan versiones configuradas.');
        }
        Schema::table('producto_versiones', fn (Blueprint $table) => $table->dropColumn('politica_mora'));
    }
};
