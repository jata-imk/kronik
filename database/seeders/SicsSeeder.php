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
        Sic::updateOrCreate(['clave' => 'buro-credito'], ['nombre' => 'Buró de Crédito']);
        $circuloCredito = Sic::updateOrCreate(['clave' => 'circulo-credito'], ['nombre' => 'Círculo de Crédito']);

        $apis = [
            ['nombre' => "FICO® Score", 'clave' => "fico_score_v2"],
            ['nombre' => "Fintech Score", 'clave' => "fintech"],
            ['nombre' => "Reporte de Crédito Fico Score", 'clave' => "rc_fico_score"],
            ['nombre' => "Reporte de Crédito Consolidado con FICO Score", 'clave' => "fico_score_consolidado"],
            ['nombre' => "Reporte de Crédito Consolidado con FICO Score v2", 'clave' => "fico_score_consolidado_v2"],
            ['nombre' => "Reporte de Crédito Consolidado con FICO Score y PLD Check MX", 'clave' => "fico_score_consolidado_pld_check"],
            ['nombre' => "FICO Extended Score v2", 'clave' => "fico_extended_score_v2"],
        ];

        foreach ($apis as $api) {
            SicApi::updateOrCreate(
                [
                    'sic_id' => $circuloCredito->id,
                    'clave' => $api['clave'],
                ],
                [
                    'nombre' => $api['nombre'],
                ]
            );
        }
    }
}
