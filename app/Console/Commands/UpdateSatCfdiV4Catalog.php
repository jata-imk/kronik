<?php

namespace App\Console\Commands;

use App\Services\Scrapers\CatalogoSatCfdiV4Service;

use Illuminate\Console\Command;
use Exception;

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

    protected $catalogoSatCfdiV4Service;

    public function __construct(CatalogoSatCfdiV4Service $satCfdiV4Service)
    {
        parent::__construct();
        $this->catalogoSatCfdiV4Service = $satCfdiV4Service;
    }

    /**
     * Execute the console command.
     *
     * @param CatalogoSatCfdiV4Service $satCfdiV4Service
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando descarga del catálogo CFDI V4.0...');

        try {
            $updated = $this->catalogoSatCfdiV4Service->downloadAndProcessCatalog();

            if ($updated) {
                $this->info('Catálogo SAT CFDI V4 actualizado correctamente.');
            } else {
                $this->info('El catálogo ya está actualizado. No se requieren cambios.');
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error al descargar el catálogo: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
