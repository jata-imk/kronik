<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

use App\Services\Scrapers\SepomexService;


class SepomexSeeder extends Seeder
{
    protected $sepomexService;

    public function __construct(SepomexService $sepomexService)
    {
        $this->sepomexService = $sepomexService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info("Ejecutando seeder de catálogo CFDI V4.0...");

        try {
            $updated = $this->sepomexService->downloadAndProcessCatalog();

            if ($updated) {
                Log::info("Catálogo actualizado correctamente desde el seeder.");
            } else {
                Log::info("Catálogo ya estaba actualizado.");
            }
        } catch (\Exception $e) {
            Log::error("Error en seeder: " . $e->getMessage());
        }
    }
}
