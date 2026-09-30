<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creditos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->unique()->constrained('solicitudes')->restrictOnDelete();
            $table->foreignId('solicitud_formalizacion_id')->unique()->constrained('solicitud_formalizaciones')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('producto_version_id')->constrained('producto_versiones')->restrictOnDelete();
            $table->string('estado', 24)->default('activo');
            $table->string('modo', 10)->default('qa');
            $table->date('fecha_desembolso');
            $table->decimal('capital_inicial', 18, 2);
            $table->longText('condiciones');
            $table->char('condiciones_hash', 64);
            $table->timestamps();
            $table->index(['sucursal_id', 'estado']);
        });
        Schema::create('credito_cronogramas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->longText('snapshot');
            $table->char('snapshot_hash', 64);
            $table->timestamps();
            $table->unique(['credito_id', 'version']);
        });
        Schema::create('credito_desembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->unique()->constrained('creditos')->restrictOnDelete();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->char('payload_hash', 64);
            $table->date('fecha_efectiva');
            $table->decimal('importe', 18, 2);
            $table->string('medio', 24)->default('transferencia');
            $table->text('referencia');
            $table->char('referencia_hash', 64)->unique();
            $table->timestamps();
        });
        Schema::create('credito_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->restrictOnDelete();
            $table->foreignId('credito_desembolso_id')->nullable()->unique()->constrained('credito_desembolsos')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo', 32);
            $table->date('fecha_efectiva');
            $table->decimal('importe', 18, 2);
            $table->timestamps();
            $table->index(['credito_id', 'fecha_efectiva']);
        });
    }

    public function down(): void
    {
        if (DB::table('creditos')->exists()) {
            throw new RuntimeException('Hay créditos conservados. No borres su historia; restaura un respaldo coherente.');
        }
        Schema::dropIfExists('credito_movimientos');
        Schema::dropIfExists('credito_desembolsos');
        Schema::dropIfExists('credito_cronogramas');
        Schema::dropIfExists('creditos');
    }
};
