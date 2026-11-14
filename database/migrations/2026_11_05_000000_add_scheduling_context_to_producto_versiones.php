<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_versiones', function (Blueprint $table) {
            // Historical scheduling context is unknown: do not infer it from the creator.
            $table->unsignedBigInteger('programada_por')->nullable();
            $table->unsignedBigInteger('programada_team_id')->nullable();
            $table->unsignedBigInteger('programada_sucursal_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('producto_versiones', fn (Blueprint $table) => $table->dropColumn([
            'programada_por', 'programada_team_id', 'programada_sucursal_id',
        ]));
    }
};
