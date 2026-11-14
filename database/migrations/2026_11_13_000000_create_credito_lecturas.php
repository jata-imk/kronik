<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credito_lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->cascadeOnDelete();
            $table->date('corte');
            $table->unsignedBigInteger('ultimo_pago_id')->nullable();
            $table->unsignedInteger('cronograma_version');
            $table->unsignedInteger('proyector_version')->default(1);
            $table->char('fuente_hash', 64);
            $table->char('condiciones_hash', 64);
            $table->char('original_hash', 64);
            $table->char('cronograma_hash', 64);
            $table->char('pago_hash', 64)->nullable();
            $table->char('estado_hash', 64)->nullable();
            $table->longText('estado_motor')->nullable();
            $table->string('estado', 24);
            foreach (['capital', 'exigible', 'vencido'] as $campo) {
                $table->decimal($campo, 18, 2)->nullable();
            }
            $table->unsignedInteger('dias_atraso')->nullable();
            $table->timestamp('calculada_at');
            $table->unique(['credito_id', 'corte']);
            $table->index(['corte', 'estado', 'dias_atraso']);
        });
        Schema::table('credito_pagos', fn (Blueprint $table) => $table->index(['credito_id', 'fecha_efectiva', 'id'], 'pagos_lectura_fecha'));
        Schema::create('credito_lectura_obligaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lectura_id')->constrained('credito_lecturas')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('naturaleza', 16);
            foreach (['capital', 'ordinario', 'moratorio', 'comisiones', 'impuestos', 'total'] as $concepto) {
                $table->decimal($concepto, 18, 2);
            }
            $table->unique(['lectura_id', 'fecha', 'naturaleza'], 'lectura_obligacion_unica');
            $table->index(['naturaleza', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('credito_pagos', fn (Blueprint $table) => $table->dropIndex('pagos_lectura_fecha'));
        Schema::dropIfExists('credito_lectura_obligaciones');
        Schema::dropIfExists('credito_lecturas');
    }
};
