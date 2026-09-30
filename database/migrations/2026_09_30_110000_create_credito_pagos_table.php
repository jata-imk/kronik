<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credito_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo', 16);
            $table->foreignId('reversa_de')->nullable()->unique()->constrained('credito_pagos')->restrictOnDelete();
            $table->date('fecha_efectiva');
            $table->decimal('importe', 18, 2);
            $table->text('referencia')->nullable();
            $table->char('referencia_hash', 64)->nullable()->unique();
            $table->text('motivo')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->char('payload_hash', 64);
            $table->longText('snapshot');
            $table->char('snapshot_hash', 64);
            $table->timestamps();
            $table->index(['credito_id', 'fecha_efectiva']);
        });
        Schema::table('credito_movimientos', function (Blueprint $table) {
            $table->foreignId('credito_pago_id')->nullable()->constrained('credito_pagos')->restrictOnDelete();
            $table->string('concepto', 100)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('credito_pagos')->exists()) {
            throw new RuntimeException('Hay pagos conservados. No borres la historia del crédito.');
        }
        Schema::table('credito_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credito_pago_id');
            $table->dropColumn('concepto');
        });
        Schema::dropIfExists('credito_pagos');
    }
};
