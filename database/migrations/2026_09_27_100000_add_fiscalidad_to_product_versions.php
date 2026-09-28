<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_versiones', fn (Blueprint $table) => $table->json('fiscalidad')->nullable());
        Schema::table('producto_version_comisiones', fn (Blueprint $table) => $table->json('fiscalidad')->nullable());
    }

    public function down(): void
    {
        Schema::table('producto_version_comisiones', fn (Blueprint $table) => $table->dropColumn('fiscalidad'));
        Schema::table('producto_versiones', fn (Blueprint $table) => $table->dropColumn('fiscalidad'));
    }
};
