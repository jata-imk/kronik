<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_configuraciones', function (Blueprint $table) {
            $table->string('tipo_persona', 6)->default('moral')->after('nombre_comercial');
            $table->foreignId('regimen_fiscal_id')
                ->nullable()
                ->after('rfc')
                ->constrained('regimenes_fiscales')
                ->nullOnDelete();
        });

        DB::table('empresa_configuraciones')
            ->select(['id', 'regimen_fiscal'])
            ->orderBy('id')
            ->each(function (object $configuracion): void {
                $regimenId = DB::table('regimenes_fiscales')
                    ->where('clave', $configuracion->regimen_fiscal)
                    ->orWhere('descripcion', $configuracion->regimen_fiscal)
                    ->value('id');

                DB::table('empresa_configuraciones')
                    ->where('id', $configuracion->id)
                    ->update(['regimen_fiscal_id' => $regimenId]);
            });

        Schema::table('empresa_configuraciones', function (Blueprint $table) {
            $table->dropColumn('regimen_fiscal');
        });
    }

    public function down(): void
    {
        Schema::table('empresa_configuraciones', function (Blueprint $table) {
            $table->string('regimen_fiscal')->nullable()->after('rfc');
        });

        DB::table('empresa_configuraciones')
            ->select(['id', 'regimen_fiscal_id'])
            ->orderBy('id')
            ->each(function (object $configuracion): void {
                $clave = $configuracion->regimen_fiscal_id
                    ? DB::table('regimenes_fiscales')->where('id', $configuracion->regimen_fiscal_id)->value('clave')
                    : null;

                DB::table('empresa_configuraciones')
                    ->where('id', $configuracion->id)
                    ->update(['regimen_fiscal' => $clave]);
            });

        Schema::table('empresa_configuraciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('regimen_fiscal_id');
            $table->dropColumn('tipo_persona');
        });
    }
};
