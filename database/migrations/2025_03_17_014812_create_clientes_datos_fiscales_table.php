<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isMariaDb = str_contains(DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION), 'MariaDB');

        Schema::create('clientes_datos_fiscales', function (Blueprint $table) use ($isMariaDb) {
            $table->id();

            $table->unsignedBigInteger('cliente_id');

            if ($isMariaDb) {
                $table->string('tipo_persona', 7);
            } else {
                $table->rawColumn('tipo_persona', 'varchar(7) not null constraint tipo_persona_check check (tipo_persona in (\'fisica\', \'moral\'))');
            }

            $table->unsignedBigInteger('regimen_fiscal_id');
            $table->string('curp');
            $table->string('rfc');
            $table->string('razon_social');

            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('regimen_fiscal_id')->references('id')->on('regimenes_fiscales');
        });

        if ($isMariaDb) {
            DB::statement("
                    CREATE TRIGGER check_tipo_persona_before_insert
                    BEFORE INSERT ON clientes_datos_fiscales
                    FOR EACH ROW
                    BEGIN
                        IF NEW.tipo_persona NOT IN ('fisica', 'moral') THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Valor inválido para tipo_persona';
                        END IF;
                    END;
                ");

            DB::statement("
                    CREATE TRIGGER check_tipo_persona_before_update
                    BEFORE UPDATE ON clientes_datos_fiscales
                    FOR EACH ROW
                    BEGIN
                        IF NEW.tipo_persona NOT IN ('fisica', 'moral') THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Valor inválido para tipo_persona';
                        END IF;
                    END;
                ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS check_tipo_persona_before_insert;");
        DB::statement("DROP TRIGGER IF EXISTS check_tipo_persona_before_update;");

        Schema::dropIfExists('clientes_datos_fiscales');
    }
};
