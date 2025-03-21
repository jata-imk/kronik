<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table) {
            $table->id();

            $table->string('nombre_es', 100)->notNullable();
            $table->string('nombre_us')->nullable();

            $table->json('nombre_nativo')->nullable()->comment('Nombres en el idioma nativo, por ejemplo: {esp: "Español", eng: "English"}');
            $table->json('idiomas')->nullable()->comment('Idiomas, por ejemplo: {esp: "Español", eng: "English"}');

            $table->string('codigo_iso', 2)->notNullable()->unique()->comment('ISO 3166-1 alpha-2');
            $table->string('codigo_iso3', 3)->notNullable()->comment('ISO 3166-1 alpha-3');

            $table->string('emoji', 3)->nullable()->comment('Emoji de la bandera del país');

            $table->json('mapas')->nullable()->comment('Links del mapa, por ejemplo: {google: "https://www.google.com/maps", openstreetmap: "https://www.openstreetmap.org"}');
            $table->json('formato_direccion')->nullable()->comment('Plantilla de formato para mostrar direcciones');

            // Ejemplo para México:
            // {
            //     "formato": [
            //       "{linea_direccion_1}",
            //       "{linea_direccion_2}",
            //       "{linea_direccion_3}",
            //       "{datos_adicionales.colonia}, CP {codigo_postal.codigo}",
            //       "{division_admin_nivel2}, {division_admin_nivel1}",
            //       "MÉXICO"
            //     ],
            //     "etiquetas": {
            //       "division_admin_nivel1": "Estado",
            //       "division_admin_nivel2": "Municipio",
            //       "division_admin_nivel3": "Localidad",
            //       "codigo_postal": "C.P.",
            //       "datos_adicionales.colonia": "Colonia"
            //     }
            //   }

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paises');
    }
};
