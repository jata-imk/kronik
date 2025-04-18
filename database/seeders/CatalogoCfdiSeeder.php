<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

use App\Services\Scrapers\CatalogoSatCfdiV4Service;

use Exception;

class CatalogoCfdiSeeder extends Seeder
{
    protected $catalogoSatCfdiV4Service;

    public function __construct(CatalogoSatCfdiV4Service $catalogoSatCfdiV4Service)
    {
        $this->catalogoSatCfdiV4Service = $catalogoSatCfdiV4Service;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('Iniciando descarga del catálogo CFDI V4.0...');

        try {
            $updated = $this->catalogoSatCfdiV4Service->downloadAndProcessCatalog();

            if ($updated) {
                Log::info('Catálogo SAT CFDI V4 actualizado correctamente.');
            } else {
                Log::info('El catálogo ya está actualizado. No se requieren cambios.');
            }
        } catch (Exception $e) {
            Log::error('Error al descargar el catálogo: ' . $e->getMessage());
        }
    }
}
