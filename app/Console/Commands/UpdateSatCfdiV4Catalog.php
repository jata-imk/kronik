<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Scrapers\CatalogoSatCfdiV4Service;

use Throwable;

class UpdateSatCfdiV4Catalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sat-cfdi-v4:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Descarga y crea/actualiza el catálogo CFDI V4.0 desde el sitio del SAT';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param CatalogoSatCfdiV4Service $catalogoSatCfdiV4Service
     * @return int
     */
    public function handle(CatalogoSatCfdiV4Service $catalogoSatCfdiV4Service): int
    {
        $this->info('Iniciando descarga del catálogo CFDI V4.0 desde el SAT...');

        try {
            $updated = $catalogoSatCfdiV4Service->downloadAndProcessCatalog();

            if ($updated) {
                $this->info('Catálogo SAT CFDI V4 actualizado correctamente.');
            } else {
                $this->info('El catálogo ya está actualizado. No se requieren cambios.');
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error al descargar el catálogo: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
