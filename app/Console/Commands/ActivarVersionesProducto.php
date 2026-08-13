<?php

namespace App\Console\Commands;

use App\Services\ProductoVersionService;
use Illuminate\Console\Command;

class ActivarVersionesProducto extends Command
{
    protected $signature = 'productos:activar-versiones';

    protected $description = 'Activa versiones de producto cuya vigencia programada ya inició';

    public function handle(ProductoVersionService $service): int
    {
        $this->info("Versiones activadas: {$service->activarProgramadas()}");

        return self::SUCCESS;
    }
}
