<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

use App\Services\Scrapers\CatalogoSatCfdiV4Service;

use Exception;

class CatalogoCfdiSeeder extends Seeder
{
    protected $catalogoSatCfdiV4Service;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Los Seeders en Laravel por defecto no soportan inyección de dependencias en el constructor porque son
        // instanciados directamente por Artisan. Entonces:
        // ✅ Lo correcto en este caso es usar el contenedor manualmente, pero con el helper app()
        $this->catalogoSatCfdiV4Service = app(CatalogoSatCfdiV4Service::class);

        // Nota: ¿Me inyectó automáticamente la dependencia en el constructor de un Seeder?
        // Laravel desde versiones recientes (8+ y mejor en 9+) puede resolver dependencias en constructores de Seeders,
        // pero no está oficialmente documentado como algo totalmente estable. Puede funcionar, pero no es garantizado
        // ni recomendable confiar ciegamente en eso.
        // Mejor: haz la inyección en el método run() usando app() si quieres ser más explícito y robusto.

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
