<?php

namespace Database\Seeders;

use App\Models\ConceptoComision;
use Illuminate\Database\Seeder;

class ConceptosComisionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['clave' => 'APERTURA', 'nombre' => 'Apertura', 'descripcion' => 'Comisión por contratación o apertura del crédito.'],
            ['clave' => 'ADMINISTRACION', 'nombre' => 'Administración', 'descripcion' => 'Comisión periódica de administración del crédito.'],
            ['clave' => 'PAGO_TARDIO', 'nombre' => 'Pago tardío', 'descripcion' => 'Comisión por pago posterior a la fecha límite; incompatible con interés moratorio en el mismo periodo.'],
            ['clave' => 'PREPAGO', 'nombre' => 'Prepago', 'descripcion' => 'Comisión asociada a un pago anticipado cuando el contrato y la normativa lo permitan.'],
        ] as $concepto) {
            ConceptoComision::updateOrCreate(['clave' => $concepto['clave']], [...$concepto, 'referencia_reco' => 'Catálogo RECO CONDUSEF', 'es_oficial_reco' => true, 'revisado' => true, 'activo' => true]);
        }
    }
}
