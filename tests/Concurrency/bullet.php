<?php

// Committed fixtures and separate PHP processes exercise actual InnoDB locks.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ActivityEvent;
use App\Models\Activity;
use App\Models\Credito;
use App\Models\CreditoPago;
use App\Models\EmpresaConfiguracion;
use App\Models\Solicitud;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\Credito\DesembolsoService;
use App\Services\Credito\PagoService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Tests\Support\BulletScenario;

if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'kronik_test'
    || ! str_contains(DB::selectOne('SELECT VERSION() AS version')->version, 'MariaDB')) {
    fwrite(STDERR, "Bullet requiere APP_ENV=testing y MariaDB aislada kronik_test. No se modificó información.\n");
    exit(1);
}

BulletScenario::habilitar();
config(['documentos.disk' => 'bullet_concurrency', 'filesystems.disks.bullet_concurrency' => [
    'driver' => 'local', 'root' => storage_path('framework/testing/bullet-concurrency-documents'), 'throw' => true,
]]);

function comprobarBullet(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

/** Both workers reach the locked query while the coordinator owns its row lock. */
function competirBullet(string $tabla, array $ids, array $operaciones): array
{
    $barrier = storage_path('framework/testing/bullet-race-'.Str::uuid());
    mkdir($barrier, 0777, true);
    $processes = [];
    try {
        DB::beginTransaction();
        DB::table($tabla)->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        foreach ($operaciones as $slot => $operation) {
            $process = new Process([PHP_BINARY, __FILE__, 'worker', json_encode([
                ...$operation, 'barrier' => $barrier, 'slot' => $slot, 'tabla' => $tabla,
                // Date casts use the mock's zone; now() would convert it to the app zone.
                'clock' => CarbonImmutable::getTestNow()->toIso8601String(),
                'timezone' => CarbonImmutable::getTestNow()->getTimezone()->getName(),
            ], JSON_THROW_ON_ERROR)], base_path());
            $process->setTimeout(45);
            $process->start();
            $processes[] = $process;
        }
        $deadline = microtime(true) + 20;
        while (count(glob($barrier.'/*.ready')) !== count($processes)) {
            foreach ($processes as $process) {
                comprobarBullet($process->isRunning(), 'Un proceso terminó antes de disputar el bloqueo: '.$process->getErrorOutput());
            }
            comprobarBullet(microtime(true) < $deadline, 'Los procesos no alcanzaron la barrera de bloqueo.');
            usleep(10000);
        }
        DB::commit();
        $results = [];
        foreach ($processes as $process) {
            comprobarBullet($process->wait() === 0, 'Falló un proceso financiero: '.$process->getErrorOutput());
            $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
        }
        comprobarBullet(count(array_unique(array_column($results, 'connection'))) === count($results), 'Se requieren conexiones independientes.');

        return $results;
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        foreach (glob($barrier.'/*') as $file) {
            unlink($file);
        }
        rmdir($barrier);
    }
}

function resultadoBullet(array $results, int $successes): void
{
    comprobarBullet(count(array_filter($results, fn ($result) => $result['ok'])) === $successes,
        'Resultado concurrente inesperado: '.json_encode($results, JSON_UNESCAPED_UNICODE));
    if ($successes === 2) {
        comprobarBullet($results[0]['id'] === $results[1]['id'], 'El reintento debe devolver el mismo registro.');
    }
}

function verificarLecturaBullet(Credito $credito): void
{
    app(\App\Services\Credito\ProyeccionCreditoService::class)->diagnosticar($credito->fresh());
    $lectura = \App\Models\CreditoLectura::where('credito_id', $credito->id)
        ->where('corte', app(\App\Services\FechaEmpresa::class)->hoy()->toDateString())->sole();
    comprobarBullet($lectura->capital === app(PagoService::class)->consultar($credito)['resumen']['capital_insoluto'], 'La lectura concurrente difiere del motor.');
}

function actividadBullet(Illuminate\Database\Eloquent\Model $sujeto, ActivityEvent $evento): int
{
    return Activity::where('subject_type', $sujeto->getMorphClass())->where('subject_id', $sujeto->id)->where('event', $evento->value)->count();
}

function prepararPagoBullet(Credito $credito, User $actor, string $tipo, string $importe, ?string $referencia = null): array
{
    $data = ['operacion' => $tipo, 'importe' => $importe, 'fecha_pago' => app(App\Services\FechaEmpresa::class)->hoy()->toDateString(),
        'referencia' => $referencia ?? 'BUL-RACE-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $data['previa_hash'] = app(PagoService::class)->previa($credito, $data, $actor)['previa_hash'];

    return ['accion' => 'registrar', 'credito' => $credito->id, 'actor' => $actor->id, 'data' => $data];
}

try {
    if (($argv[1] ?? null) === 'worker') {
        $operation = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        $clock = CarbonImmutable::parse($operation['clock'])->setTimezone($operation['timezone']);
        CarbonImmutable::setTestNow($clock);
        Illuminate\Support\Carbon::setTestNow($clock);
        $connection = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
        $actor = User::findOrFail($operation['actor']);
        auth()->setUser($actor);
        setPermissionsTeamId($actor->current_team_id);
        $ready = false;
        DB::connection()->beforeExecuting(function ($sql) use ($operation, &$ready) {
            if (! $ready && str_contains(strtolower($sql), 'from `'.$operation['tabla'].'`') && str_contains(strtolower($sql), 'for update')) {
                $ready = true;
                file_put_contents($operation['barrier'].'/'.$operation['slot'].'.ready', 'ready');
            }
        });
        try {
            $result = match ($operation['accion']) {
                'desembolso' => app(DesembolsoService::class)->registrar(Solicitud::findOrFail($operation['solicitud']), $operation['data'], $actor),
                'registrar' => app(PagoService::class)->registrar(Credito::findOrFail($operation['credito']), $operation['data'], $actor),
                'reversar' => app(PagoService::class)->reversar(Credito::findOrFail($operation['credito']), CreditoPago::findOrFail($operation['pago']), $operation['data'], $actor),
            };
            echo json_encode(['ok' => true, 'id' => $result->id, 'connection' => $connection], JSON_THROW_ON_ERROR);
        } catch (ValidationException $exception) {
            echo json_encode(['ok' => false, 'errors' => $exception->errors(), 'connection' => $connection], JSON_THROW_ON_ERROR);
        }
        exit(0);
    }

    Queue::fake();
    BulletScenario::pdfSintetico();
    EmpresaConfiguracion::updateOrCreate(['singleton_key' => 'default'], ['estatus' => 'activa', 'tipo_persona' => 'fisica',
        'razon_social' => 'Financiera sintética', 'rfc' => 'GODE561231GR8', 'email' => 'race@example.test', 'zona_horaria' => 'America/Mexico_City']);
    $branch = Sucursal::create(['nombre' => 'Bullet concurrencia', 'clave' => 'BUL'.Str::random(10), 'activa' => true]);
    $actor = User::factory()->withPersonalTeam()->create(['is_super_admin' => true, 'current_sucursal_id' => $branch->id, 'sucursal_principal_id' => $branch->id]);
    $actor->forceFill(['current_team_id' => $actor->ownedTeams()->sole()->id])->save();
    $actor->sucursales()->attach($branch);
    auth()->setUser($actor);
    setPermissionsTeamId($actor->current_team_id);
    $date = static function (string $date): void {
        $clock = CarbonImmutable::parse($date.' 12:00:00', 'America/Mexico_City');
        CarbonImmutable::setTestNow($clock);
        Illuminate\Support\Carbon::setTestNow($clock);
    };

    foreach (['periodicos', 'al_vencimiento'] as $policy) {
        $date('2027-01-01');
        $version = BulletScenario::producto($actor, $policy);
        $loans = [];
        foreach ([true, false] as $sameKey) {
            $solicitud = BulletScenario::aprobar(BulletScenario::solicitud($actor, $version), $actor);
            $package = BulletScenario::formalizar($solicitud, $actor);
            $data = BulletScenario::desembolso($solicitud, $package);
            $first = ['accion' => 'desembolso', 'actor' => $actor->id, 'solicitud' => $solicitud->id, 'data' => $data];
            $second = $first;
            if (! $sameKey) {
                $second['data']['idempotency_key'] = (string) Str::uuid();
                $second['data']['referencia'] = 'BUL-DES-'.Str::uuid();
            }
            resultadoBullet(competirBullet('solicitudes', [$solicitud->id], [$first, $second]), $sameKey ? 2 : 1);
            $loans[] = $solicitud->credito()->sole();
            comprobarBullet(actividadBullet($solicitud, ActivityEvent::CreditDisbursed) === 1, 'El desembolso debe auditarse una sola vez sobre la solicitud.');
        }
        [$loan, $other] = $loans;
        $originals = array_map(fn ($credit) => [$credit->condiciones_hash, $credit->cronogramas()->where('version', 1)->sole()->snapshot_hash], $loans);

        $date('2027-01-16');
        $advance = prepararPagoBullet($loan, $actor, 'prepago', '3120.00');
        resultadoBullet(competirBullet('creditos', [$loan->id], [$advance, $advance]), 2);
        comprobarBullet($loan->pagos()->count() === 1 && $loan->cronogramas()->count() === 2 && actividadBullet($loan, ActivityEvent::CreditPaymentRecorded) === 1, 'Anticipo o auditoría duplicados.');
        comprobarBullet(app(PagoService::class)->consultar($loan)['resumen']['capital_insoluto'] === '8880.00', 'Se aplicó principal más de una vez.');
        verificarLecturaBullet($loan);
        $reverse = ['accion' => 'reversar', 'actor' => $actor->id, 'credito' => $loan->id, 'pago' => $loan->pagos()->sole()->id,
            'data' => ['idempotency_key' => (string) Str::uuid(), 'motivo' => 'Corrección concurrente sintética Bullet.', 'confirmacion_qa' => true]];
        resultadoBullet(competirBullet('creditos', [$loan->id], [$reverse, $reverse]), 2);
        comprobarBullet($loan->pagos()->count() === 2 && $loan->cronogramas()->count() === 3 && actividadBullet($loan, ActivityEvent::CreditPaymentReversed) === 1, 'Compensación o cronograma duplicados.');
        comprobarBullet(app(PagoService::class)->consultar($loan)['resumen']['capital_insoluto'] === '12000.00', 'El reverso no restauró principal.');
        verificarLecturaBullet($loan);

        $prepay = prepararPagoBullet($other, $actor, 'prepago', '3120.00');
        $settle = prepararPagoBullet($other, $actor, 'liquidacion', '12120.00');
        resultadoBullet(competirBullet('creditos', [$other->id], [$prepay, $settle]), 1);
        comprobarBullet($other->pagos()->count() === 1 && $other->cronogramas()->count() === 2 && actividadBullet($other, ActivityEvent::CreditPaymentRecorded) === 1, 'La previa obsoleta escribió datos.');

        // Same reference on distinct loans also exercises the unique index and rollback.
        $date('2027-01-01');
        $extra = [];
        foreach (range(1, 2) as $slot) {
            $application = BulletScenario::aprobar(BulletScenario::solicitud($actor, $version), $actor);
            $package = BulletScenario::formalizar($application, $actor);
            $extra[] = app(DesembolsoService::class)->registrar($application->fresh(), BulletScenario::desembolso($application, $package), $actor);
        }
        $date('2027-01-16');
        $reference = 'BUL-UNIQUE-'.Str::uuid();
        $a = prepararPagoBullet($extra[0], $actor, 'prepago', '3120.00', $reference);
        $b = prepararPagoBullet($extra[1], $actor, 'prepago', '3120.00', strtolower($reference));
        resultadoBullet(competirBullet('creditos', array_map(fn ($c) => $c->id, $extra), [$a, $b]), 1);
        comprobarBullet(array_sum(array_map(fn ($c) => $c->pagos()->count(), $extra)) === 1
            && array_sum(array_map(fn ($c) => $c->cronogramas()->count(), $extra)) === 3
            && array_sum(array_map(fn ($c) => actividadBullet($c, ActivityEvent::CreditPaymentRecorded), $extra)) === 1, 'La referencia duplicada no revirtió toda la operación.');

        $date('2027-03-01');
        $payA = prepararPagoBullet($loan, $actor, 'pago', '100.00');
        $payB = prepararPagoBullet($loan, $actor, 'pago', '100.00');
        resultadoBullet(competirBullet('creditos', [$loan->id], [$payA, $payB]), 1);
        $last = $loan->pagos()->latest('id')->firstOrFail();
        $another = prepararPagoBullet($loan, $actor, 'pago', '100.00');
        $reverse['pago'] = $last->id;
        $reverse['data']['idempotency_key'] = (string) Str::uuid();
        resultadoBullet(competirBullet('creditos', [$loan->id], [$reverse, $another]), 1);
        comprobarBullet($loan->pagos()->count() === 4, 'La competencia entre reverso y pago no fue atómica.');
        foreach ($loans as $index => $credit) {
            comprobarBullet([$credit->fresh()->condiciones_hash, $credit->cronogramas()->where('version', 1)->sole()->snapshot_hash] === $originals[$index], 'Se modificó evidencia original.');
            $activities = Activity::query()->where(function ($query) use ($credit) {
                $query->where(function ($query) use ($credit) {
                    $query->where('subject_type', $credit->getMorphClass())->where('subject_id', $credit->id);
                })->orWhere(function ($query) use ($credit) {
                    $query->where('subject_type', $credit->solicitud->getMorphClass())->where('subject_id', $credit->solicitud_id)
                        ->where('event', ActivityEvent::CreditDisbursed->value);
                });
            })->get();
            foreach ($activities as $activity) {
                comprobarBullet($activity->causer_id === $actor->id && $activity->team_id === $actor->current_team_id && $activity->sucursal_id === $branch->id, 'Auditoría con actor o contexto incorrectos.');
                comprobarBullet(! str_contains($activity->toJson(), '3120.00') && ! str_contains($activity->toJson(), 'Corrección concurrente'), 'Auditoría con datos reservados.');
            }
        }
        echo "Bullet {$policy}: ocho competencias financieras multiconexión aprobadas.\n";
    }
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage()."\n");
    exit(1);
}
