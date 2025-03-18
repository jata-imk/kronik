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
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('entidad_id');
            $table->rawColumn('entidad_tipo', 'varchar(10) not null constraint entidad_tipo_check check (entidad_tipo in (\'clientes\'))');
            
            $table->rawColumn('tipo', 'varchar(10) not null constraint tipo_check check (tipo in (\'personal\', \'fiscal\'))');

            $table->unsignedBigInteger('pais_id');
            $table->unsignedBigInteger('codigo_postal_id')->nullable();
            
            // Las líneas de dirección (línea_uno, línea_dos y línea_tres) sirven para propósitos
            // diferentes que las divisiones administrativas y no entran en conflicto con ellas.
            // Representan información complementaria:
                
            // Líneas de dirección (línea_uno, línea_dos, línea_tres):
            // Contienen los detalles específicos de la ubicación física
            // Incluyen información como calle, número, apartamento, edificio, etc.
            // Son los datos necesarios para llegar exactamente al lugar
            $table->string('linea_uno', 255)->comment('Usada comúnmente para indicar la calle y número exterior e interior.');
            $table->string('linea_dos', 127)->nullable()->comment('Información adicional como número de apartamento, piso, edificio. Ejemplos: "Apartamento 5B" o "Edificio Torre Norte, Piso 7"');
            $table->string('linea_tres', 127)->nullable()->comment('Usada comúnmente para indicar la colonia. Tambien para datos complementarios como referencias, urbanización, etc. Ejemplos: "Cerca del Parque Central" o "Urbanización Los Pinos"');

            // Divisiones administrativas:
            // Representan la jerarquía geográfica/política oficial del territorio
            // Definen la jurisdicción a la que pertenece la dirección. https://es.wikipedia.org/wiki/Divisiones_administrativas_por_país
            $table->unsignedBigInteger('division_admin_uno_id')->comment('México: Estado, Ecuador: Provincia, Peru: Departamento, EUA: State, etc.');
            $table->unsignedBigInteger('division_admin_dos_id')->comment('México: Municipio, Ecuador: Canton, Peru: Provincia, EUA: County, etc.');
            $table->unsignedBigInteger('division_admin_tres_id')->nullable()->comment('México: Localidad/Asentamiento (Cuando sea diferente al municipio), Ecuador: Parroquia, Peru: Distrito, EUA: Township/District, etc.');

            // Para información adicional específica por país
            $table->json('datos_adicionales')->nullable()->comment('Dependiendo del país. Para Mexico puede ser: Ciudad, Localidad, Asentamiento, Tipo de asentamiento, etc.');
            
            $table->rawColumn('coordenadas', 'POINT');

            $table->timestamps();

            $table->foreign('pais_id')->references('id')->on('paises');
            $table->foreign('codigo_postal_id')->references('id')->on('codigos_postales');
            $table->foreign('division_admin_uno_id')->references('id')->on('divisiones_administrativas');
            $table->foreign('division_admin_dos_id')->references('id')->on('divisiones_administrativas');
            $table->foreign('division_admin_tres_id')->references('id')->on('divisiones_administrativas');

            $table->spatialIndex('coordenadas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};
