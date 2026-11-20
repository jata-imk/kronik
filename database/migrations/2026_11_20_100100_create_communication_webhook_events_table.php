<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->foreignId('communication_delivery_id')->nullable()->constrained('communication_deliveries');
            $table->string('provider', 30);
            $table->string('provider_message_id', 255);
            $table->string('event_key', 64)->unique();
            $table->string('status', 30);
            $table->timestamp('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_webhook_events');
    }
};
