<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_version_comisiones', function (Blueprint $table) {
            $table->string('modalidad_cobro', 30)->nullable()->after('momento_cobro');
        });
    }

    public function down(): void
    {
        Schema::table('producto_version_comisiones', function (Blueprint $table) {
            $table->dropColumn('modalidad_cobro');
        });
    }
};
