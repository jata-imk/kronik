<?php

namespace App\Console\Commands;

use App\Models\SicQuery;
use App\Services\SICs\SicOperacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CifrarSicHistorico extends Command
{
    protected $signature = 'sic:cifrar-historico {--retirar-texto-plano : Retira copias verificadas después del respaldo} {--dry-run}';

    protected $description = 'Copia respuestas SIC heredadas a evidencia cifrada sin cambiar IDs ni procedencia';

    public function handle(SicOperacionService $service): int
    {
        $count = 0;
        SicQuery::where(fn ($q) => $q->whereNotNull('response_data')->orWhereNotNull('mensaje_error')
            ->orWhereHas('results', fn ($r) => $r->whereNotNull('data')))->orderBy('id')->chunkById(100, function ($queries) use ($service, &$count) {
                foreach ($queries as $query) {
                    $count++;
                    if ($this->option('dry-run')) {
                        continue;
                    }
                    DB::transaction(function () use ($query, $service) {
                        $q = SicQuery::lockForUpdate()->findOrFail($query->id);
                        $contenido = ['response_data' => $q->response_data, 'mensaje_error' => $q->mensaje_error,
                            'results' => $q->results()->orderBy('id')->get()->map(fn ($r) => $r->getAttributes())->all()];
                        if (! $q->evidencias()->where('tipo', 'archivo_heredado')->exists()) {
                            $service->evidencia($q, 'archivo_heredado', $contenido);
                            $body = $q->response_data;
                            if (is_string($body)) {
                                $body = json_decode($body, true);
                            }
                            if (! is_array($body)) {
                                $body = [];
                                foreach ($q->results()->orderBy('id')->get() as $result) {
                                    if (! is_array($result->data)) {
                                        continue;
                                    }
                                    $key = match ($result->tipo_registro) {
                                        'score' => 'scores', 'credito' => 'creditos', 'consulta' => 'consultas', 'domicilio' => 'domicilios', 'empleo' => 'empleos', default => null
                                    };
                                    if ($key) {
                                        $body[$key][] = $result->data;
                                    }
                                }
                            }
                            $service->evidencia($q, 'historico', $body ?: ['contenido_no_reconocido' => true]);
                        }
                        $archivado = $service->leer($q, 'archivo_heredado');
                        if ($archivado !== $contenido) {
                            throw new \RuntimeException('El archivo heredado no coincide. Se conservó el texto plano.');
                        }
                        if ($this->option('retirar-texto-plano')) {
                            $q->update(['response_data' => null, 'mensaje_error' => null]);
                            $q->results()->update(['data' => null]);
                        }
                    });
                }
            });
        $this->info('Registros examinados: '.$count.'. No se modificó la procedencia de los históricos.');

        return self::SUCCESS;
    }
}
