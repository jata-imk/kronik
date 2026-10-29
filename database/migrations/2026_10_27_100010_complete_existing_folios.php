<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(\App\Services\FolioService::class)->completarHistoricos();
    }

    public function down(): void
    {
        // Assigned business identifiers are never removed or reused.
    }
};
