<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_rules', function (Blueprint $table) {
            $table->unsignedSmallInteger('lead_days')->default(3)->after('event_type');
        });
    }

    public function down(): void
    {
        Schema::table('communication_rules', fn (Blueprint $table) => $table->dropColumn('lead_days'));
    }
};
