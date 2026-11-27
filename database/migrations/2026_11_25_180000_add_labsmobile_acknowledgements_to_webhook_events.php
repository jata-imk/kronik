<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_webhook_events', function (Blueprint $table) {
            $table->string('provider_correlation_id', 20)->nullable();
            $table->string('ack_level', 20)->nullable();
            $table->string('provider_status', 10)->nullable();
            $table->string('provider_code', 30)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('applied_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('communication_webhook_events', function (Blueprint $table) {
            $table->dropColumn(['provider_correlation_id', 'ack_level', 'provider_status', 'provider_code', 'occurred_at', 'applied_at']);
        });
    }
};
