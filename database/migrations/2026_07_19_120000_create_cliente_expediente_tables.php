<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('ocupacion', 127)->nullable()->after('sexo');
            $table->string('actividad_economica', 255)->nullable()->after('ocupacion');
            $table->decimal('ingresos_mensuales', 15, 2)->nullable()->after('actividad_economica');
            $table->decimal('egresos_mensuales', 15, 2)->nullable()->after('ingresos_mensuales');
            $table->text('origen_recursos')->nullable()->after('egresos_mensuales');
        });

        Schema::create('cliente_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('reemplaza_documento_id')->nullable()->constrained('cliente_documentos')->nullOnDelete();
            $table->string('tipo', 40);
            $table->string('nombre', 127)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('es_actual')->default(true);
            $table->string('disk', 40)->nullable();
            $table->string('path')->nullable();
            $table->string('nombre_original')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->timestamp('recibido_en')->nullable();
            $table->timestamp('revisado_en')->nullable();
            $table->date('vence_en')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_rechazo')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'tipo', 'es_actual']);
        });

        Schema::create('cliente_referencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->string('nombre', 255);
            $table->string('relacion', 127)->nullable();
            $table->string('empresa', 255)->nullable();
            $table->string('puesto', 127)->nullable();
            $table->string('telefono_codigo_pais', 4)->nullable();
            $table->string('telefono', 15);
            $table->string('email', 127)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('cliente_vinculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('cliente_vinculado_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('rol', 30);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'cliente_vinculado_id', 'rol']);
        });

        Schema::create('cliente_garantias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('propietario_cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('descripcion', 255);
            $table->decimal('valor_estimado', 15, 2)->nullable();
            $table->char('moneda', 3)->default('MXN');
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('cliente_consentimientos_sic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->string('medio', 30);
            $table->timestamp('otorgado_en');
            $table->date('vence_en')->nullable();
            $table->timestamp('revocado_en')->nullable();
            $table->string('evidencia_disk', 40);
            $table->string('evidencia_path');
            $table->string('evidencia_nombre_original');
            $table->string('evidencia_mime_type', 100);
            $table->unsignedBigInteger('evidencia_tamano_bytes');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'otorgado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_consentimientos_sic');
        Schema::dropIfExists('cliente_garantias');
        Schema::dropIfExists('cliente_vinculos');
        Schema::dropIfExists('cliente_referencias');
        Schema::dropIfExists('cliente_documentos');

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'ocupacion',
                'actividad_economica',
                'ingresos_mensuales',
                'egresos_mensuales',
                'origen_recursos',
            ]);
        });
    }
};
