<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_deliveries', function (Blueprint $table) {
            $table->string('provider', 40)->nullable();
            $table->string('provider_correlation_id', 20)->nullable()->unique('communication_delivery_correlation_unique');
        });
    }

    public function down(): void
    {
        Schema::table('communication_deliveries', function (Blueprint $table) {
            $table->dropUnique('communication_delivery_correlation_unique');
            $table->dropColumn(['provider', 'provider_correlation_id']);
        });
    }
};
