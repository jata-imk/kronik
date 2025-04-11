<?php

namespace App\Console\Commands;

use App\Services\CatalogoSatCfdiV4Service;

use App\Services\BrowserClientService;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

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

    protected $satCfdiV4Service;

    public function __construct()
    {
        parent::__construct();
        $http = HttpClient::create([
            'timeout' => 60,
            'verify_host' => false,
        ]);

        $browserClient = new BrowserClientService(new HttpBrowser($http));
        $this->satCfdiV4Service = new CatalogoSatCfdiV4Service($http, $browserClient);
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
            $updated = $this->satCfdiV4Service->downloadAndProcessCatalog();

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
