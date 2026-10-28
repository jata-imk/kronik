<?php

namespace App\Console\Commands;

use App\Services\FolioService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class CompletarFolios extends Command
{
    protected $signature = 'folios:completar {--dry-run : Comprueba configuración y cuenta pendientes sin escribir}';

    protected $description = 'Completa identificadores de negocio históricos sin modificar documentos ni snapshots';

    public function handle(FolioService $service): int
    {
        try {
            $pendientes = $this->option('dry-run') ? $service->comprobarHistoricos() : $service->completarHistoricos();
            foreach ($pendientes as $tipo => $cantidad) {
                $this->line($tipo.': '.$cantidad);
            }
            $this->info($this->option('dry-run') ? 'Comprobación terminada sin escrituras.' : 'Folios completados; los existentes se conservaron.');

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }
    }
}
