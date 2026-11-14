<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->string('event', 80);
            $table->string('priority', 16)->default('normal');
            $table->string('title', 160);
            $table->text('body');
            $table->string('dedup_key', 160);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id', 'dedup_key'], 'operator_notifications_dedup_unique');
            $table->index(['user_id', 'team_id', 'sucursal_id', 'read_at'], 'operator_notifications_inbox_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_notifications');
    }
};
