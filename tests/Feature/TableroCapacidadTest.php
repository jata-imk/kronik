<?php

use App\Models\Credito;
use App\Services\Credito\ProyeccionCreditoService;
use App\Services\TableroService;
use App\Support\Decimal as D;
use Illuminate\Support\Facades\DB;

test('mide lectura actualizacion y reconstruccion con cartera sintetica aislada', function () {
    if (getenv('KRONIK_TABLERO_BENCHMARK') !== '1') {
        $this->markTestSkipped('Medición explícita: KRONIK_TABLERO_BENCHMARK=1.');
    }
    expect(app()->environment('testing'))->toBeTrue()->and(DB::connection()->getDatabaseName())->toBe(':memory:');
    ini_set('memory_limit', '1536M');
    $this->withoutVite();
    [$actor, $credito] = creditoPagableQa($this);
    $dias = max(1, min(90, (int) (getenv('KRONIK_TABLERO_DIAS') ?: 1)));
    $this->travelTo(\Carbon\CarbonImmutable::parse($credito->fecha_desembolso->toDateString().' 12:00:00', 'America/Mexico_City')->addDays($dias - 1));
    $total = min(10000, max(100, (int) (getenv('KRONIK_TABLERO_VOLUMEN') ?: 10000)));
    $soloLectura = getenv('KRONIK_TABLERO_FASE') === 'lectura';
    if ($soloLectura) {
        app(ProyeccionCreditoService::class)->sincronizar($credito);
    }
    $formalizacion = DB::table('solicitud_formalizaciones')->where('id', $credito->solicitud_formalizacion_id)->first();
    $paquete = DB::table('solicitud_paquetes')->where('id', $formalizacion->solicitud_paquete_id)->first();
    $fuentes = [
        'solicitudes' => DB::table('solicitudes')->where('id', $credito->solicitud_id)->first(),
        'solicitud_revisiones' => DB::table('solicitud_revisiones')->where('id', $paquete->solicitud_revision_id)->first(),
        'solicitud_resoluciones' => DB::table('solicitud_resoluciones')->where('id', $paquete->solicitud_resolucion_id)->first(),
        'solicitud_paquetes' => $paquete,
        'solicitud_firmas' => DB::table('solicitud_firmas')->where('id', $formalizacion->solicitud_firma_id)->first(),
        'solicitud_formalizaciones' => $formalizacion,
        'creditos' => DB::table('creditos')->where('id', $credito->id)->first(),
        'credito_cronogramas' => DB::table('credito_cronogramas')->where('credito_id', $credito->id)->first(),
        'credito_desembolsos' => DB::table('credito_desembolsos')->where('credito_id', $credito->id)->first(),
        'credito_movimientos' => DB::table('credito_movimientos')->where('credito_id', $credito->id)->first(),
    ];
    $referencias = ['solicitud_id' => 'solicitudes', 'solicitud_revision_id' => 'solicitud_revisiones',
        'solicitud_resolucion_id' => 'solicitud_resoluciones', 'solicitud_paquete_id' => 'solicitud_paquetes',
        'solicitud_firma_id' => 'solicitud_firmas', 'solicitud_formalizacion_id' => 'solicitud_formalizaciones',
        'credito_id' => 'creditos', 'credito_desembolso_id' => 'credito_desembolsos'];
    $bases = [];
    foreach ($fuentes as $tabla => $fuente) {
        $bases[$tabla] = DB::table($tabla)->max('id');
    }
    // Replicación de evidencia financiera sintética; no acredita 10 mil originaciones.
    foreach ($fuentes as $tabla => $fuente) {
        $filas = [];
        for ($i = 1; $i < $total; $i++) {
            $fila = (array) $fuente;
            $fila['id'] = $bases[$tabla] + $i;
            foreach ($referencias as $columna => $destino) {
                if (isset($fila[$columna])) {
                    $fila[$columna] = $bases[$destino] + $i;
                }
            }
            foreach (['idempotency_key', 'clave_creacion'] as $columna) {
                if (isset($fila[$columna])) {
                    $fila[$columna] = (string) str()->uuid();
                }
            }
            if (array_key_exists('folio', $fila)) {
                $fila['folio'] = 'BENCH-'.$tabla.'-'.$i;
            }
            if (isset($fila['referencia_hash'])) {
                $fila['referencia_hash'] = hash('sha256', $tabla.'-'.$i);
            }
            $filas[] = $fila;
            if (count($filas) === 200 || $i === $total - 1) {
                DB::table($tabla)->insert($filas);
                $filas = [];
            }
        }
    }
    if ($soloLectura) {
        $plantilla = (array) DB::table('credito_lecturas')->where('credito_id', $credito->id)->where('corte', app(\App\Services\FechaEmpresa::class)->hoy()->toDateString())->sole();
        $obligaciones = DB::table('credito_lectura_obligaciones')->where('lectura_id', $plantilla['id'])->get();
        $baseLecturas = DB::table('credito_lecturas')->max('id');
        for ($i = 1; $i < $total; $i++) {
            DB::table('credito_lecturas')->insert([...$plantilla, 'id' => $baseLecturas + $i, 'credito_id' => $bases['creditos'] + $i]);
            DB::table('credito_lectura_obligaciones')->insert($obligaciones->map(function ($o) use ($baseLecturas, $i) {
                $fila = (array) $o;
                unset($fila['id']);

                return [...$fila, 'lectura_id' => $baseLecturas + $i];
            })->all());
        }
    }
    // Cada actualización productiva confirma su transacción raíz. Conservar la
    // envoltura RefreshDatabase acumularía miles de SAVEPOINT en SQLite y mediría
    // ese arnés en lugar del proceso real. La base es exclusivamente :memory:.
    expect(DB::transactionLevel())->toBe(1);
    DB::commit();
    $proyecciones = app(ProyeccionCreditoService::class);
    $medirProceso = function (bool $reconstruir = false, string $fase = 'construccion') use ($proyecciones, $total) {
        $inicio = hrtime(true);
        $procesados = 0;
        Credito::query()->select('id')->chunkById(100, function ($creditos) use ($proyecciones, $reconstruir, $fase, $inicio, $total, &$procesados) {
            foreach ($creditos as $item) {
                $proyecciones->sincronizar($item, reconstruir: $reconstruir);
                $procesados++;
            }
            file_put_contents(storage_path('framework/testing/tablero-capacidad-progreso.json'), json_encode([
                'fase' => $fase, 'procesados' => $procesados, 'total' => $total, 'segundos' => round((hrtime(true) - $inicio) / 1e9, 2),
            ], JSON_PRETTY_PRINT));
        });

        return round((hrtime(true) - $inicio) / 1e9, 3);
    };
    $construccion = $soloLectura ? null : $medirProceso();
    if (! $soloLectura) {
        $this->travel(1)->days();
    }
    $actualizacion = $soloLectura ? null : $medirProceso(false, 'avance_diario');
    $reconstruccion = $soloLectura ? null : $medirProceso(true, 'reconstruccion');
    $tablero = app(TableroService::class);
    $filtros = $tablero->filtros([]);
    $alcance = ['scope' => 'all', 'sucursal_id' => null, 'requiereSucursal' => false];
    $tiempos = [];
    $consultas = [];
    for ($i = 0; $i < 5; $i++) {
        DB::enableQueryLog();
        $inicio = hrtime(true);
        $resultado = $tablero->consultar($alcance, $filtros);
        $tiempos[] = round((hrtime(true) - $inicio) / 1e6, 2);
        $consultas[] = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();
        expect($resultado['cobertura']['verificados'])->toBe($total)->and($resultado['saldos']['capital'])->toBe(D::mul($credito->capital_inicial, (string) $total, 2));
    }
    $evidencia = ['fecha_ejecucion' => (new DateTimeImmutable)->format(DATE_ATOM), 'corte_sintetico' => $filtros['corte'], 'dias_desde_desembolso' => $dias,
        'php' => PHP_VERSION, 'plataforma' => PHP_OS_FAMILY, 'bd' => 'SQLite :memory:',
        'creditos' => $total, 'metodo' => 'simple con condiciones del fixture QA', 'cuotas_por_credito' => count($credito->cronogramas()->first()->snapshot['tabla']) - 1,
        'construccion_s' => $construccion, 'avance_un_dia_s' => $actualizacion, 'reconstruccion_s' => $reconstruccion,
        'lecturas_ms' => $tiempos, 'consultas_por_lectura' => $consultas, 'memoria_pico_mib' => round(memory_get_peak_usage(true) / 1048576, 1),
        'limites' => 'Sin red ni concurrencia; no certifica MariaDB ni otros métodos, productos futuros o tiempos de originación.'];
    $nombre = $soloLectura ? 'tablero-capacidad-lectura.json' : 'tablero-capacidad.json';
    file_put_contents(storage_path('framework/testing/'.$nombre), json_encode($evidencia, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->group('capacidad-tablero');
