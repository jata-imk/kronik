<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Scrapers\SepomexService;

use Throwable;

class UpdateSepomexCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sepomex:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Descarga y actualiza el catálogo de códigos postales de SEPOMEX';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(SepomexService $sepomexService)
    {
        $this->info('Iniciando actualización del catálogo SEPOMEX...');

        try {
            $updated = $sepomexService->downloadAndProcessCatalog();

            if ($updated) {
                $this->info('Catálogo SEPOMEX actualizado correctamente.');
            } else {
                $this->info('El catálogo ya está actualizado. No se requieren cambios.');
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
