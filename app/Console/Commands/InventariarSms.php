<?php

namespace App\Console\Commands;

use App\Services\Communications\SmsMigrationInventory;
use Illuminate\Console\Command;

final class InventariarSms extends Command
{
    protected $signature = 'comunicaciones:inventariar-sms {--json : Devuelve únicamente el informe JSON}';

    protected $description = 'Observa conexiones, salidas, callbacks y colas antes del corte SMS, sin escribir ni enviar';

    public function handle(SmsMigrationInventory $inventory): int
    {
        $report = $inventory->collect();
        if (! $this->option('json')) {
            $this->info('Inventario SMS de solo lectura. No se modificaron datos ni se enviaron mensajes.');
            $this->warn('El informe no autoriza el corte ni atribuye un proveedor a salidas sin evidencia.');
        }
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $report['complete'] ? self::SUCCESS : self::FAILURE;
    }
}
