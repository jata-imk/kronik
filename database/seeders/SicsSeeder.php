<?php

namespace Database\Seeders;

use App\Models\Sic;
use App\Models\SicApi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SicsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sic::create(['nombre' => 'Buró de Crédito', 'clave' => 'buro-credito']);
        Sic::create(['nombre' => 'Círculo de Crédito', 'clave' => 'circulo-credito']);

        SicApi::create(['sic_id' => 2, 'nombre' => "FICO® Score", 'clave' => "fico_score_v2"]);
        SicApi::create(['sic_id' => 2, 'nombre' => "Fintech Score", 'clave' => "fintech"]);
        SicApi::create(['sic_id' => 2, 'nombre' => "Reporte de Crédito Fico Score", 'clave' => "rc_fico_score"]);
        SicApi::create(['sic_id' => 2, 'nombre' => "Reporte de Crédito Consolidado con FICO Score", 'clave' => "fico_score_consolidado"]);
        SicApi::create(['sic_id' => 2, 'nombre' => "Reporte de Crédito Consolidado con FICO Score v2", 'clave' => "fico_score_consolidado_v2"]);
        SicApi::create(['sic_id' => 2, 'nombre' => "Reporte de Crédito Consolidado con FICO Score y PLD Check MX", 'clave' => "fico_score_consolidado_pld_check"]);
        SicApi::create(['sic_id' => 2, 'nombre' => "FICO Extended Score v2", 'clave' => "fico_extended_score_v2"]);
    }
}
