<?php

namespace App\Console\Commands;

use App\Services\Communications\SmsTestDataCleanup;
use Illuminate\Console\Command;
use Throwable;

class CleanSmsTestData extends Command
{
    protected $signature = 'comunicaciones:limpiar-sms-prueba
        {--apply : Aplica la limpieza selectiva; sin esta opción solo muestra conteos}
        {--confirm-qa : Confirma que la instalación solo contiene datos desechables de desarrollo o QA}';

    protected $description = 'Retira SMS anteriores de prueba sin purgar correo, Meta ni colas compartidas';

    public function handle(SmsTestDataCleanup $service): int
    {
        if ($this->option('apply') && (! $this->option('confirm-qa')
            || (! app()->environment(['local', 'testing']) && ! app()->isDownForMaintenance()))) {
            $this->error('Confirma el uso exclusivo de datos de prueba con --confirm-qa. Fuera de local/testing, activa mantenimiento y detén workers y programador.');

            return self::FAILURE;
        }
        try {
            $report = $service->run((bool) $this->option('apply'));
            $this->info($this->option('apply') ? 'Limpieza SMS aplicada.' : 'Diagnóstico SMS: no se modificaron datos.');
            $labels = ['channels' => 'Configuraciones anteriores', 'deliveries' => 'Salidas por invalidar',
                'rules' => 'Reglas por desactivar', 'callbacks' => 'Callbacks anteriores', 'jobs' => 'Jobs identificados',
                'unclassified_jobs' => 'Jobs no clasificados conservados', 'uninspected_stores' => 'Almacenes no inspeccionados conservados'];
            foreach ($report as $key => $count) {
                $this->line($labels[$key].': '.$count);
            }
            $this->line('Los jobs antiguos conservados no pueden despachar salidas invalidadas. No se vaciaron colas compartidas.');

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('No se completó la limpieza. Verifica las barreras desactivadas, la disponibilidad de BD y la configuración de QA; no se imprimen datos sensibles.');

            return self::FAILURE;
        }
    }
}
