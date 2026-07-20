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

        Schema::create('clientes', function (Blueprint $table) use ($isMariaDb) {
            $table->id();
            $table->string('primer_nombre', 127);
            $table->string('segundo_nombre', 127);
            $table->string('apellido_paterno', 127);
            $table->string('apellido_materno', 127);

            $table->date('fecha_nacimiento');
            $table->bigInteger('pais_nacimiento_id');

            $table->string('telefono_codigo_pais', 4);
            $table->string('telefono', 15);
            $table->string('email', 127);

            if ($isMariaDb) {
                $table->string('sexo', 15);
            } else {
                $table->rawColumn('sexo', 'varchar(15) constraint sexo_check check (sexo in (\'masculino\', \'femenino\'))');
            }

            $table->timestamps();
        });

        if ($isMariaDb) {
            DB::statement("
                    CREATE TRIGGER check_sexo_before_insert
                    BEFORE INSERT ON clientes
                    FOR EACH ROW
                    BEGIN
                        IF NEW.sexo NOT IN ('masculino', 'femenino') THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Valor inválido para sexo';
                        END IF;
                    END;
                ");

            DB::statement("
                    CREATE TRIGGER check_sexo_before_update
                    BEFORE UPDATE ON clientes
                    FOR EACH ROW
                    BEGIN
                        IF NEW.sexo NOT IN ('masculino', 'femenino') THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Valor inválido para sexo';
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
        // Eliminar los triggers antes de borrar la tabla
        DB::statement("DROP TRIGGER IF EXISTS check_sexo_before_insert;");
        DB::statement("DROP TRIGGER IF EXISTS check_sexo_before_update;");

        Schema::dropIfExists('clientes');
    }
};
