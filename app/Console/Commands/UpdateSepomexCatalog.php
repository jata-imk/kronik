<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SepomexService;

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

    protected $sepomexService;

    public function __construct(SepomexService $sepomexService)
    {
        parent::__construct();
        $this->sepomexService = $sepomexService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando actualización del catálogo SEPOMEX...');

        try {
            $updated = $this->sepomexService->downloadAndProcessCatalog();

            if ($updated) {
                $this->info('Catálogo SEPOMEX actualizado correctamente.');
            } else {
                $this->info('El catálogo ya está actualizado. No se requieren cambios.');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
