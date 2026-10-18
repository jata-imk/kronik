<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('development_data_resets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('mode', 16);
            $table->string('operator');
            $table->unsignedBigInteger('preserved_admin_id')->nullable();
            $table->json('counts');
            $table->unsignedBigInteger('file_count');
            // Encrypted manifest survives deletion of its source records.
            $table->longText('pending_files');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('development_data_resets')->whereNull('completed_at')->exists()) {
            throw new RuntimeException('Hay limpiezas de archivos pendientes. Complétalas antes de retirar su registro.');
        }

        Schema::dropIfExists('development_data_resets');
    }
};
