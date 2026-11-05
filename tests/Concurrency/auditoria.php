<?php

// Independent committed connections: SQLite/RefreshDatabase cannot prove locking.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Activity;
use App\Models\ProductoCrediticio;
use App\Services\FechaEmpresa;
use App\Services\ProductoVersionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'kronik_test') {
    fwrite(STDERR, "Concurrencia requiere APP_ENV=testing y MariaDB aislada kronik_test. No se modificó información.\n");
    exit(1);
}

try {
    if (($argv[1] ?? null) === 'worker') {
        $barrier = $argv[2];
        $slot = $argv[3];
        $ready = false;
        DB::listen(function ($query) use ($barrier, $slot, &$ready) {
            if ($ready || ! str_contains($query->sql, 'from `producto_versiones`')
                || ! str_contains($query->sql, '`estado` =') || str_contains($query->sql, 'for update')) {
                return;
            }
            $ready = true;
            file_put_contents($barrier.'/'.$slot, 'ready');
            $deadline = microtime(true) + 20;
            while (count(glob($barrier.'/*')) < 3) {
                if (microtime(true) > $deadline) {
                    throw new RuntimeException('Los tres procesos no alcanzaron la barrera.');
                }
                usleep(10000);
            }
        });
        echo app(ProductoVersionService::class)->activarProgramadas();
        exit(0);
    }

    $today = app(FechaEmpresa::class)->hoy();
    $product = ProductoCrediticio::create(['clave' => 'AUD-'.Str::random(10), 'nombre' => 'Concurrencia sintética']);
    $base = ['monto_minimo' => '5000', 'monto_maximo' => '10000', 'tasa_ordinaria_anual' => '36'];
    $first = $product->versiones()->create([...$base, 'numero' => 1, 'estado' => 'activa', 'vigente_desde' => $today->subDays(3)->toDateString()]);
    $scheduled = [];
    foreach ([2, 3] as $number) {
        $scheduled[] = $product->versiones()->create([...$base, 'numero' => $number, 'estado' => 'programada',
            'vigente_desde' => $today->subDays(4 - $number)->toDateString()]);
    }
    $barrier = storage_path('framework/testing/audit-concurrency-'.Str::uuid());
    mkdir($barrier, 0777, true);
    $processes = [];
    foreach (range(1, 3) as $slot) {
        $process = new Process([PHP_BINARY, __FILE__, 'worker', $barrier, (string) $slot], base_path());
        $process->setTimeout(60);
        $process->start();
        $processes[] = $process;
    }
    $total = 0;
    foreach ($processes as $process) {
        if ($process->wait() !== 0) {
            throw new RuntimeException('Un proceso concurrente falló: '.$process->getErrorOutput());
        }
        $total += (int) $process->getOutput();
    }
    if ($total !== 2 || $product->versiones()->where('estado', 'activa')->count() !== 1
        || $scheduled[1]->fresh()->estado !== \App\Enums\ProductoVersionEstado::Activa) {
        throw new RuntimeException('Deben activarse dos versiones una vez y quedar una sola vigente.');
    }
    foreach ($scheduled as $version) {
        if (Activity::where('event', 'producto_crediticio.activated')->where('subject_type', $version->getMorphClass())
            ->where('subject_id', $version->id)->count() !== 1) {
            throw new RuntimeException('Una activación automática se perdió o se duplicó.');
        }
    }
    foreach ([$first, $scheduled[0]] as $version) {
        if (Activity::where('event', 'producto_crediticio.retired')->where('subject_type', $version->getMorphClass())
            ->where('subject_id', $version->id)->count() !== 1) {
            throw new RuntimeException('Un retiro automático se perdió o se duplicó.');
        }
    }
    foreach (glob($barrier.'/*') as $file) {
        unlink($file);
    }
    rmdir($barrier);
    echo "Concurrencia de auditoría aprobada: tres conexiones, dos activaciones y dos retiros únicos.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}
