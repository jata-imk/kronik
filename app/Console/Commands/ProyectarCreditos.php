<?php

namespace App\Console\Commands;

use App\Enums\ActivityEvent;
use App\Models\Credito;
use App\Services\ActivityLogService;
use App\Services\Credito\ProyeccionCreditoService;
use Illuminate\Console\Command;
use Throwable;

class ProyectarCreditos extends Command
{
    protected $signature = 'creditos:proyectar {--credito= : Limita a un crédito} {--reconstruir : Recalcula desde evidencia original} {--diagnosticar : Compara sin escribir ni auditar}';

    protected $description = 'Actualiza hoy y cierres mensuales de cartera sin contabilizar devengos';

    public function handle(ProyeccionCreditoService $proyecciones): int
    {
        $id = $this->option('credito');
        if ($id !== null && (! ctype_digit((string) $id) || (int) $id < 1 || ! Credito::whereKey($id)->exists())) {
            $this->error('Selecciona un crédito existente mediante su identificador.');

            return self::FAILURE;
        }
        $fallos = 0;
        $procesados = 0;
        Credito::query()->when($id, fn ($q) => $q->whereKey($id))->select('id')->chunkById(100, function ($creditos) use ($proyecciones, &$fallos, &$procesados) {
            foreach ($creditos as $item) {
                try {
                    $credito = Credito::findOrFail($item->id);
                    if ($this->option('diagnosticar')) {
                        $proyecciones->diagnosticar($credito);
                    } else {
                        $proyecciones->sincronizar($credito, reconstruir: (bool) $this->option('reconstruir'));
                    }
                    $procesados++;
                } catch (Throwable $e) {
                    if (! $this->option('diagnosticar')) {
                        $proyecciones->invalidar(Credito::findOrFail($item->id));
                    }
                    $fallos++;
                    $this->error('Crédito #'.$item->id.': no se pudo verificar su lectura; revisa la evidencia y ejecuta el diagnóstico.');
                    report($e);
                }
            }
        });
        if ($this->option('reconstruir') && ! $this->option('diagnosticar')) {
            app(ActivityLogService::class)->log(ActivityEvent::CreditProjectionsRebuilt, 'Reconstrucción de lecturas financieras ejecutada',
                metadata: ['result' => $fallos ? 'incompleta' : 'completada'], inheritActorContext: false);
        }
        $this->info("Créditos verificados: $procesados. Con incidencias: $fallos.");

        return $fallos ? self::FAILURE : self::SUCCESS;
    }
}
