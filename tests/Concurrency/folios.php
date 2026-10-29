<?php

// Standalone integration test: committed rows and genuinely separate database
// connections cannot be exercised inside RefreshDatabase's outer transaction.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cliente;
use App\Models\Solicitud;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'kronik_test') {
    fwrite(STDERR, "Concurrencia requiere APP_ENV=testing y MariaDB aislada kronik_test. No se modificó información.\n");
    exit(1);
}

try {
    if (in_array($argv[1] ?? null, ['worker', 'mixed_service', 'mixed_direct'], true)) {
        $data = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        $mode = $argv[1];
        if ($mode !== 'worker') {
            $esperada = false;
            DB::listen(function ($query) use ($mode, $data, &$esperada) {
                $tabla = $mode === 'mixed_service' ? 'users' : 'sucursales';
                $sql = strtolower($query->sql);
                if ($esperada || ! str_contains($sql, 'from `'.$tabla.'`') || ! str_contains($sql, 'for update')) {
                    return;
                }
                $esperada = true;
                $propio = $mode === 'mixed_service' ? 'actor' : 'sucursal';
                $otro = $mode === 'mixed_service' ? 'sucursal' : 'actor';
                file_put_contents($data['barrera'].'/'.$propio, 'bloqueado');
                $limite = microtime(true) + 15;
                while (! is_file($data['barrera'].'/'.$otro)) {
                    if (microtime(true) >= $limite) {
                        throw new RuntimeException('La otra transacción no alcanzó la barrera de concurrencia.');
                    }
                    usleep(10000);
                }
            });
        }
        $espera = max(0, $data['inicio'] - microtime(true));
        usleep((int) ($espera * 1000000));
        if ($mode === 'mixed_service') {
            $actor = User::findOrFail($data['actor']);
            app(\App\Services\SolicitudService::class)->crear(['cliente_id' => $data['cliente'],
                'clave_creacion' => (string) Str::uuid()], $actor);
            exit(0);
        }
        for ($i = 0; $i < ($mode === 'worker' ? 5 : 1); $i++) {
            Solicitud::create(['cliente_id' => $data['cliente'], 'sucursal_id' => $data['sucursal'],
                'creada_por' => $data['actor'], 'responsable_id' => $data['actor'],
                'clave_creacion' => (string) Str::uuid(), 'creacion_hash' => str_repeat('a', 64)]);
        }
        exit(0);
    }

    $sucursal = Sucursal::create(['nombre' => 'Concurrencia sintética', 'clave' => 'CONC'.strtoupper(Str::random(8)),
        'prefijo_folio' => 'T'.strtoupper(Str::random(8)), 'activa' => true]);
    $actor = User::factory()->create(['is_super_admin' => true, 'current_sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id]);
    $inicio = microtime(true) + 2;
    $procesos = [];
    for ($i = 0; $i < 8; $i++) {
        $process = new Process([PHP_BINARY, __FILE__, 'worker', json_encode(['actor' => $actor->id,
            'cliente' => $cliente->id, 'sucursal' => $sucursal->id, 'inicio' => $inicio], JSON_THROW_ON_ERROR)], base_path());
        $process->setTimeout(60);
        $process->start();
        $procesos[] = $process;
    }
    foreach ($procesos as $process) {
        if ($process->wait() !== 0) {
            fwrite(STDERR, $process->getErrorOutput());
            throw new RuntimeException('Un proceso independiente no pudo confirmar sus cinco solicitudes.');
        }
    }
    // Reproduce the actor -> folio / folio -> actor FK cycle that can arise
    // between application creation and payment/model insertion. Different
    // clients prevent a client lock from accidentally serializing this case.
    $barrera = storage_path('framework/testing/folio-concurrency-'.Str::uuid());
    mkdir($barrera, 0777, true);
    $otroCliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id]);
    $mixtos = [];
    foreach (['mixed_service' => $cliente->id, 'mixed_direct' => $otroCliente->id] as $mode => $clienteId) {
        $process = new Process([PHP_BINARY, __FILE__, $mode, json_encode(['actor' => $actor->id,
            'cliente' => $clienteId, 'sucursal' => $sucursal->id, 'inicio' => microtime(true), 'barrera' => $barrera], JSON_THROW_ON_ERROR)], base_path());
        $process->setTimeout(60);
        $process->start();
        $mixtos[] = $process;
    }
    foreach ($mixtos as $process) {
        if ($process->wait() !== 0) {
            fwrite(STDERR, $process->getErrorOutput());
            throw new RuntimeException('Los dos caminos deben confirmar después de resolver el cruce de bloqueos.');
        }
    }
    unlink($barrera.'/actor');
    unlink($barrera.'/sucursal');
    rmdir($barrera);
    $folios = Solicitud::where('sucursal_id', $sucursal->id)->orderBy('folio')->pluck('folio')->all();
    $esperados = array_map(fn ($numero) => $sucursal->prefijo_folio.'-SOL-'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT), range(1, 42));
    if ($folios !== $esperados || DB::table('folio_asignaciones')->where('sucursal_id', $sucursal->id)->count() !== 42) {
        throw new RuntimeException('Las cuarenta creaciones deben tener identificadores consecutivos únicos y reservas persistidas.');
    }
    // No enclosing test transaction: exercise TieneFolio's direct save wrapper.
    $fechada = Solicitud::create(['cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id,
        'creada_por' => $actor->id, 'responsable_id' => $actor->id,
        'clave_creacion' => (string) Str::uuid(), 'creacion_hash' => str_repeat('a', 64),
        'created_at' => '2020-01-01 10:00:00', 'updated_at' => '2020-01-02 11:00:00'])->fresh();
    if ($fechada->created_at->format('Y-m-d H:i:s') !== '2020-01-01 10:00:00'
        || $fechada->updated_at->format('Y-m-d H:i:s') !== '2020-01-02 11:00:00'
        || $fechada->folio !== $sucursal->prefijo_folio.'-SOL-000043'
        || DB::table('folio_asignaciones')->where('sucursal_id', $sucursal->id)->count() !== 43) {
        throw new RuntimeException('La creación directa debe conservar fechas explícitas y asignar el siguiente folio.');
    }
    fwrite(STDOUT, "Concurrencia MariaDB aprobada: 40 creaciones paralelas y 2 caminos con cruce de bloqueos; 42 folios consecutivos únicos.\n");
    fwrite(STDOUT, "Creación directa con fechas explícitas aprobada; folio 43 persistido.\n");
} catch (Throwable $exception) {
    // Never dump environment, connection arguments or arbitrary exception data.
    fwrite(STDERR, 'Falló la comprobación de concurrencia: '.get_class($exception).' en línea '.$exception->getLine().".\n");
    if ($exception instanceof \Illuminate\Validation\ValidationException) {
        fwrite(STDERR, json_encode($exception->errors(), JSON_UNESCAPED_UNICODE)."\n");
    } elseif ($exception instanceof \Illuminate\Database\QueryException) {
        fwrite(STDERR, 'SQLSTATE '.($exception->errorInfo[0] ?? '?').' / código '.($exception->errorInfo[1] ?? '?')."\n");
    }
    exit(1);
}
