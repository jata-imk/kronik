<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_generados', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('sucursal_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('documentos_generados', fn (Blueprint $table) => $table->dropColumn(['team_id', 'sucursal_id']));
    }
};
