<?php

namespace Database\Seeders;

use App\Models\Pais;
use GuzzleHttp\Client;
use Illuminate\Database\Seeder;

class PaisesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // TODO: Revisar fecha de la ultima actualización y si es menor a X tiempo no se actualiza

        $paises = [];

        // Opción 1: Desde API externa como restcountries.com
        try {
            $client = new Client([
                'timeout' => 60,
                'verify' => false, // Considera cambiar a true en producción
            ]);

            $response = $client->get('https://restcountries.com/v3.1/all?fields=name,translations,languages,cca2,cca3,flag,flags,maps');
            if ($response->getStatusCode() == 200) {
                $paises = $response->getBody()->getContents();
                $paises = json_decode(
                    $paises,
                    true,
                    512,
                    JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
                );
            }
        } catch (\Throwable $th) {
            // throw $th;
        }

        if (empty($paises)) {
            // Opción 2: Desde archivo JSON local
            $paises = json_decode(
                file_get_contents(database_path('data/countries.json')),
                true,
                512,
                JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            );
        }

        $paises = array_filter(
            $paises,
            fn ($pais) => is_array($pais)
                && isset($pais['name']['common'], $pais['cca2'], $pais['cca3']),
        );

        if (empty($paises)) {
            $paises = json_decode(
                file_get_contents(database_path('data/countries.json')),
                true,
                512,
                JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            );
        }

        $paises = array_map(function ($pais) {
            return [
                'nombre_es' => $pais['translations']['spa']['common'] ?? $pais['name']['common'],
                'nombre_us' => $pais['name']['common'],
                'nombre_nativo' => json_encode($pais['name']['nativeName'] ?? []),
                'idiomas' => json_encode($pais['languages'] ?? []),
                'codigo_iso' => $pais['cca2'],
                'codigo_iso3' => $pais['cca3'],
                'emoji' => $pais['flag'] ?? null,
                'mapas' => json_encode($pais['maps'] ?? []),
            ];
        }, $paises);

        // Ejemplo de un objeto del JSON
        // {
        //     "flags": {
        //         "png": "https://flagcdn.com/w320/mx.png",
        //         "svg": "https://flagcdn.com/mx.svg",
        //         "alt": "The flag of Mexico is composed of three equal vertical bands of green, white and red, with the national coat of arms centered in the white band."
        //     },
        //     "name": {
        //         "common": "Mexico",
        //         "official": "United Mexican States",
        //         "nativeName": {
        //             "spa": {
        //                 "official": "Estados Unidos Mexicanos",
        //                 "common": "México"
        //             }
        //         }
        //     },
        //     "cca2": "MX",
        //     "cca3": "MEX",
        //     "languages": {
        //         "spa": "Spanish"
        //     },
        //     "translations": {
        //         "spa": {
        //             "official": "Estados Unidos Mexicanos",
        //             "common": "México"
        //         }
        //     },
        //     "flag": "🇲🇽",
        //     "maps": {
        //         "googleMaps": "https://goo.gl/maps/s5g7imNPMDEePxzbA",
        //         "openStreetMaps": "https://www.openstreetmap.org/relation/114686"
        //     }
        // }

        Pais::upsert($paises, uniqueBy: ['codigo_iso'], update: ['nombre_es', 'nombre_us', 'nombre_nativo', 'idiomas', 'emoji', 'mapas']);

        // TODO: Actualizar la fecha de la actualización
    }
}
