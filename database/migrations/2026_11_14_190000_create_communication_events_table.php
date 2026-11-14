<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 120)->unique();
            $table->string('event_type', 40);
            $table->foreignId('credito_id')->constrained('creditos')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('source_pago_id')->nullable()->constrained('credito_pagos')->restrictOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 40)->nullable();
            $table->timestamps();
            $table->index(['event_type', 'cancelled_at', 'due_date']);
            $table->index(['credito_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_events');
    }
};
