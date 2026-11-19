<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ActivityEvent;
use App\Models\Activity;
use App\Models\CalendarioCrediticioVersion;
use App\Models\User;
use App\Services\Credito\CalendarioCrediticioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'kronik_test') {
    fwrite(STDERR, "Concurrencia requiere MariaDB aislada kronik_test.\n");
    exit(1);
}

try {
    if (($argv[1] ?? null) === 'worker') {
        [, , $barrera, $turno, $version, $actor] = $argv;
        touch($barrera.'/'.$turno);
        $limite = microtime(true) + 20;
        while (count(glob($barrera.'/*')) < 3) {
            if (microtime(true) >= $limite) {
                throw new RuntimeException('Los procesos no alcanzaron la barrera de publicación.');
            }
            usleep(10000);
        }
        app(CalendarioCrediticioService::class)->publicar(
            CalendarioCrediticioVersion::findOrFail($version), 1, User::findOrFail($actor),
        );
        exit(0);
    }

    $actor = User::factory()->create(['is_super_admin' => true]);
    $calendario = app(CalendarioCrediticioService::class);
    $datos = ['configuracion' => config('calendario-crediticio.cnbv_2026')];
    $primera = $calendario->guardar(null, $datos, $actor);
    $segunda = $calendario->guardar(null, $datos, $actor);
    $barrera = storage_path('framework/testing/calendario-'.Str::uuid());
    mkdir($barrera, 0777, true);
    $procesos = [];
    foreach ([$primera, $primera, $segunda] as $turno => $version) {
        $proceso = new Process([PHP_BINARY, __FILE__, 'worker', $barrera, (string) $turno, (string) $version->id, (string) $actor->id], base_path());
        $proceso->setTimeout(60);
        $proceso->start();
        $procesos[] = $proceso;
    }
    foreach ($procesos as $proceso) {
        if ($proceso->wait() !== 0) {
            throw new RuntimeException('Una publicación concurrente falló: '.$proceso->getErrorOutput());
        }
    }
    $ordenes = [];
    foreach ([$primera, $segunda] as $version) {
        $publicada = $version->fresh();
        if (! $publicada->publicada_en || Activity::where('event', ActivityEvent::CreditCalendarPublished->value)
            ->where('subject_type', $version->getMorphClass())->where('subject_id', $version->id)->count() !== 1) {
            throw new RuntimeException('Cada versión debe publicarse y auditarse exactamente una vez.');
        }
        $ordenes[] = (int) $publicada->orden_publicacion;
    }
    if (abs($ordenes[0] - $ordenes[1]) !== 1 || (int) $calendario->vigente()->orden_publicacion !== max($ordenes)) {
        throw new RuntimeException('La última publicación debe resolverse mediante un orden único y consistente.');
    }
    foreach (glob($barrera.'/*') as $archivo) {
        unlink($archivo);
    }
    rmdir($barrera);
    echo "Concurrencia de calendario aprobada: dos versiones y tres intentos.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
}
