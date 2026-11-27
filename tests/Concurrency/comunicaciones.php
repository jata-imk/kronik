<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Activity;
use App\Models\CommunicationChannel;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationRule;
use App\Models\EmpresaConfiguracion;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\Communications\CommunicationOrchestrator;
use App\Services\Communications\NotificationTemplateService;
use App\Services\Credito\DesembolsoService;
use App\Services\Credito\PagoService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Support\BulletScenario;

if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'kronik_test'
    || ! str_contains(DB::selectOne('SELECT VERSION() AS version')->version, 'MariaDB')) {
    fwrite(STDERR, "Comunicaciones requiere APP_ENV=testing y MariaDB aislada kronik_test. No se modificó información.\n");
    exit(1);
}

function comprobarComunicaciones(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function relojComunicaciones(string $date): void
{
    $clock = CarbonImmutable::parse($date.' 12:00:00', 'America/Mexico_City');
    CarbonImmutable::setTestNow($clock);
    Illuminate\Support\Carbon::setTestNow($clock);
}

// Enable only the synthetic process: neither transport can contact a real service.
Http::preventStrayRequests();
Http::fake(fn ($request) => Http::response(['code' => '0', 'subid' => $request['subid']]));
Mail::fake();
Queue::fake();
config(['comunicaciones.send_enabled' => true, 'comunicaciones.sms_send_enabled' => true,
    'comunicaciones.sms.username' => 'synthetic-i4', 'comunicaciones.sms.token' => 'synthetic-i4',
    'comunicaciones.sms.sender' => 'KRONIKQA', 'comunicaciones.sms.rate_cache_store' => 'array']);

$processes = [];
$barrier = null;
try {
    if (($argv[1] ?? null) === 'worker') {
        $operation = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        relojComunicaciones($operation['date']);
        $ready = false;
        DB::connection()->beforeExecuting(function ($sql, $bindings) use ($operation, &$ready) {
            if (isset($operation['credit']) && str_contains($sql, 'from `creditos`') && ! in_array($operation['credit'], $bindings)) {
                return;
            }
            if (! $ready && ((str_contains($sql, 'for update')
                && (str_contains($sql, 'from `creditos`') || str_contains($sql, 'from `clientes`') || str_contains($sql, 'from `communication_deliveries`')))
                || (($operation['action'] ?? '') === 'prepare' && str_starts_with($sql, 'insert into `communication_deliveries`')))) {
                $ready = true;
                file_put_contents($operation['barrier'].'/'.$operation['slot'].'.ready', 'ready');
            }
        });
        $connection = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
        $result = ($operation['action'] ?? '') === 'prepare'
            ? ['prepared' => app(CommunicationOrchestrator::class)->prepare()]
            : ['sent' => app(CommunicationOrchestrator::class)->send($operation['delivery'])];
        echo json_encode([...$result, 'connection' => $connection], JSON_THROW_ON_ERROR);
        exit(0);
    }

    BulletScenario::habilitar();
    BulletScenario::pdfSintetico();
    config(['documentos.disk' => 'communications_concurrency', 'filesystems.disks.communications_concurrency' => [
        'driver' => 'local', 'root' => storage_path('framework/testing/communications-concurrency-documents'), 'throw' => true,
    ]]);
    EmpresaConfiguracion::updateOrCreate(['singleton_key' => 'default'], ['estatus' => 'activa', 'tipo_persona' => 'fisica',
        'razon_social' => 'Financiera sintética', 'rfc' => 'GODE561231GR8', 'email' => 'race@example.test', 'zona_horaria' => 'America/Mexico_City']);
    $branch = Sucursal::create(['nombre' => 'Comunicaciones concurrencia', 'clave' => 'COM'.Str::random(10), 'activa' => true]);
    $actor = User::factory()->withPersonalTeam()->create(['is_super_admin' => true, 'current_sucursal_id' => $branch->id, 'sucursal_principal_id' => $branch->id]);
    $actor->forceFill(['current_team_id' => $actor->ownedTeams()->sole()->id])->save();
    $actor->sucursales()->attach($branch);
    auth()->setUser($actor);
    setPermissionsTeamId($actor->current_team_id);
    relojComunicaciones('2027-01-01');
    $product = BulletScenario::producto($actor, 'periodicos');
    $application = BulletScenario::aprobar(BulletScenario::solicitud($actor, $product), $actor);
    $package = BulletScenario::formalizar($application, $actor);
    $credit = app(DesembolsoService::class)->registrar($application->fresh(), BulletScenario::desembolso($application, $package), $actor);
    $secondApplication = app(App\Services\SolicitudService::class)->crear([
        'cliente_id' => $credit->cliente_id, 'clave_creacion' => (string) Str::uuid(), 'producto_version_id' => $product->id,
        'monto' => '12000.00', 'plazo' => 2, 'periodicidad' => 'mensual', 'metodo' => 'bullet',
        'destino' => 'Segunda operación sintética del mismo cliente.', 'fecha_estimada' => '2027-01-01',
    ], $actor);
    $secondApplication = BulletScenario::aprobar($secondApplication->fresh(), $actor);
    $secondPackage = BulletScenario::formalizar($secondApplication, $actor);
    $otherCredit = app(DesembolsoService::class)->registrar($secondApplication->fresh(), BulletScenario::desembolso($secondApplication, $secondPackage), $actor);
    $paymentDate = $credit->cronogramas()->where('version', 1)->sole()->snapshot['tabla'][1]['fecha'];
    relojComunicaciones($paymentDate);
    $payments = app(PagoService::class);
    $recordedPayments = [];
    foreach ([$credit, $otherCredit] as $loan) {
        app(App\Services\Credito\ProyeccionCreditoService::class)->sincronizar($loan);
        $payment = ['operacion' => 'pago', 'importe' => '100.00', 'fecha_pago' => $paymentDate,
            'referencia' => 'COM-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
        $payment['previa_hash'] = $payments->previa($loan, $payment, $actor)['previa_hash'];
        $recordedPayments[$loan->id] = $payments->registrar($loan, $payment, $actor);
    }
    $credit->cliente->update(['email' => 'concurrencia@example.test', 'telefono_codigo_pais' => '52', 'telefono' => '5512345678']);
    $service = app(CommunicationOrchestrator::class);
    foreach (['email' => 'smtp', 'sms' => 'labsmobile'] as $name => $provider) {
        CommunicationChannel::create(['team_id' => $actor->current_team_id, 'channel' => $name, 'provider' => $provider,
            'sender' => $name === 'email' ? 'avisos@example.test' : null, 'eligibility' => 'approved', 'enabled' => true]);
        $template = app(NotificationTemplateService::class)->create(['key' => 'race-'.Str::uuid(), 'name' => 'Concurrencia sintética',
            'event_type' => 'payment_received', 'channel' => $name, 'format' => 'text', 'subject' => 'Pago recibido', 'body' => 'Hola {nombre}.']);
        app(NotificationTemplateService::class)->activate($template->versions()->sole());
        CommunicationRule::create(['team_id' => $actor->current_team_id, 'event_type' => 'payment_received', 'channel' => $name,
            'notification_template_id' => $template->id, 'body' => '', 'enabled' => true, 'daily_limit' => 1,
            'timezone' => 'America/Mexico_City', 'window_start' => '00:00:00', 'window_end' => '23:59:59']);
        $service->grantConsent($credit->cliente->fresh(), $actor->current_team_id,
            ['channel' => $name, 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'EVID-I4'], $actor);
    }
    comprobarComunicaciones($service->prepare() >= 4 && $service->process() >= 4, 'Deben prepararse los dos canales de ambos créditos sintéticos.');
    comprobarComunicaciones(CommunicationDelivery::whereIn('credito_id', [$credit->id, $otherCredit->id])->count() === 4, 'Deben existir exactamente los cuatro avisos de este escenario.');
    $deliveries = CommunicationDelivery::where(fn ($query) => $query->where('credito_id', $credit->id)->where('channel', 'email'))
        ->orWhere(fn ($query) => $query->where('credito_id', $otherCredit->id)->where('channel', 'sms'))->orderBy('id')->get();
    $barrier = storage_path('framework/testing/communications-race-'.Str::uuid());
    mkdir($barrier, 0777, true);
    // Both transactions establish their initial view before the coordinator releases the locks.
    DB::beginTransaction();
    DB::table('creditos')->whereIn('id', [$credit->id, $otherCredit->id])->orderBy('id')->lockForUpdate()->get();
    DB::table('clientes')->where('id', $credit->cliente_id)->lockForUpdate()->get();
    DB::table('communication_deliveries')->whereIn('id', $deliveries->modelKeys())->orderBy('id')->lockForUpdate()->get();
    foreach ($deliveries as $slot => $delivery) {
        $process = new Process([PHP_BINARY, __FILE__, 'worker', json_encode(['delivery' => $delivery->id,
            'date' => $paymentDate, 'barrier' => $barrier, 'slot' => $slot], JSON_THROW_ON_ERROR)], base_path());
        $process->setTimeout(45);
        $process->start();
        $processes[] = $process;
    }
    $deadline = microtime(true) + 20;
    while (count(glob($barrier.'/*.ready')) !== 2) {
        foreach ($processes as $process) {
            comprobarComunicaciones($process->isRunning(), 'Un worker terminó antes del bloqueo: '.$process->getErrorOutput().$process->getOutput());
        }
        comprobarComunicaciones(microtime(true) < $deadline, 'Los dos workers no alcanzaron la barrera.');
        usleep(10000);
    }
    DB::commit();
    $results = [];
    foreach ($processes as $process) {
        comprobarComunicaciones($process->wait() === 0, 'Falló un worker: '.$process->getErrorOutput().$process->getOutput());
        $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
    }
    comprobarComunicaciones(count(array_unique(array_column($results, 'connection'))) === 2, 'Las conexiones deben ser independientes.');
    comprobarComunicaciones(count(array_filter($results, fn ($result) => $result['sent'])) === 1, 'El límite diario compartido permitió más de un canal.');
    comprobarComunicaciones(CommunicationDelivery::whereIn('id', $deliveries->modelKeys())->where('status', 'accepted')->count() === 1, 'Debe existir una única aceptación.');
    comprobarComunicaciones(CommunicationDelivery::whereIn('id', $deliveries->modelKeys())->where('status', 'queued')->whereNull('attempted_at')->count() === 1, 'El otro canal debe conservarse sin intento.');
    comprobarComunicaciones(Activity::where('event', 'communication.delivery_sending')->whereIn('subject_id', $deliveries->modelKeys())->count() === 1, 'El intento debe auditarse una sola vez.');
    // A reversal commits while a queued worker waits. It must read the new financial evidence.
    foreach (glob($barrier.'/*') as $file) {
        unlink($file);
    }
    $pending = CommunicationDelivery::whereIn('id', $deliveries->modelKeys())->where('status', 'queued')->sole();
    CommunicationRule::where('team_id', $pending->team_id)->where('channel', $pending->channel)->update(['daily_limit' => 2]);
    DB::beginTransaction();
    DB::table('creditos')->where('id', $pending->credito_id)->lockForUpdate()->get();
    DB::table('communication_deliveries')->where('id', $pending->id)->lockForUpdate()->get();
    $worker = new Process([PHP_BINARY, __FILE__, 'worker', json_encode(['delivery' => $pending->id,
        'date' => $paymentDate, 'barrier' => $barrier, 'slot' => 'reversal'], JSON_THROW_ON_ERROR)], base_path());
    $worker->setTimeout(45);
    $worker->start();
    $processes[] = $worker;
    $deadline = microtime(true) + 20;
    while (! file_exists($barrier.'/reversal.ready')) {
        comprobarComunicaciones($worker->isRunning(), 'El worker terminó antes del reverso: '.$worker->getErrorOutput().$worker->getOutput());
        comprobarComunicaciones(microtime(true) < $deadline, 'El worker no alcanzó el bloqueo para reverso.');
        usleep(10000);
    }
    $reversedCredit = App\Models\Credito::findOrFail($pending->credito_id);
    $payments->reversar($reversedCredit, $recordedPayments[$pending->credito_id], [
        'idempotency_key' => (string) Str::uuid(), 'motivo' => 'Reverso sintético antes del despacho.', 'confirmacion_qa' => true,
    ], $actor);
    DB::commit();
    comprobarComunicaciones($worker->wait() === 0, 'Falló la revalidación financiera: '.$worker->getErrorOutput().$worker->getOutput());
    $result = json_decode(trim($worker->getOutput()), true, flags: JSON_THROW_ON_ERROR);
    comprobarComunicaciones(! $result['sent'] && $pending->fresh()->status === 'cancelled' && $pending->fresh()->attempted_at === null,
        'Un reverso confirmado no impidió el intento del worker en espera.');
    app(App\Services\Credito\ProyeccionCreditoService::class)->diagnosticar($reversedCredit->fresh());

    // Preparation also inserts a credit FK: it must not own the template before the credit.
    foreach (glob($barrier.'/*') as $file) {
        unlink($file);
    }
    $accepted = CommunicationDelivery::whereIn('id', $deliveries->modelKeys())->where('status', 'accepted')->sole();
    $currentCredit = App\Models\Credito::findOrFail($accepted->credito_id);
    $queued = CommunicationDelivery::where('credito_id', $currentCredit->id)->where('status', 'queued')->sole();
    CommunicationRule::where('team_id', $accepted->team_id)->update(['daily_limit' => 1]);
    $nextData = ['operacion' => 'pago', 'importe' => '1.00', 'fecha_pago' => $paymentDate,
        'referencia' => 'COM-PREPARE-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $nextData['previa_hash'] = $payments->previa($currentCredit, $nextData, $actor)['previa_hash'];
    $nextPayment = $payments->registrar($currentCredit, $nextData, $actor);
    $nextEvent = App\Models\CommunicationEvent::where('event_key', 'payment:'.$nextPayment->id)->sole();
    DB::beginTransaction();
    DB::table('creditos')->where('id', $currentCredit->id)->lockForUpdate()->get();
    $crossing = [];
    foreach (['send', 'prepare'] as $action) {
        $process = new Process([PHP_BINARY, __FILE__, 'worker', json_encode(['delivery' => $queued->id,
            'credit' => $currentCredit->id, 'action' => $action, 'date' => $paymentDate,
            'barrier' => $barrier, 'slot' => $action], JSON_THROW_ON_ERROR)], base_path());
        $process->setTimeout(45);
        $process->start();
        $processes[] = $process;
        $crossing[$action] = $process;
        $deadline = microtime(true) + 20;
        while (! file_exists($barrier.'/'.$action.'.ready')) {
            comprobarComunicaciones($process->isRunning(), 'Un proceso terminó antes del cruce prepare/send: '.$process->getErrorOutput().$process->getOutput());
            comprobarComunicaciones(microtime(true) < $deadline, 'El cruce prepare/send no alcanzó el bloqueo.');
            usleep(10000);
        }
    }
    DB::commit();
    $crossResults = [];
    foreach ($crossing as $action => $process) {
        comprobarComunicaciones($process->wait() === 0, 'El cruce prepare/send falló: '.$process->getErrorOutput().$process->getOutput());
        $crossResults[$action] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
    }
    comprobarComunicaciones($crossResults['send']['connection'] !== $crossResults['prepare']['connection'], 'El cruce necesita conexiones distintas.');
    comprobarComunicaciones(! $crossResults['send']['sent'] && $crossResults['prepare']['prepared'] >= 2,
        'Preparación y envío deben terminar sin exceder el cupo ni perder candidatos.');
    comprobarComunicaciones(CommunicationDelivery::where('communication_event_id', $nextEvent->id)->count() === 2, 'El cruce debe conservar una salida por canal del nuevo evento.');
    echo "Concurrencia de comunicaciones aprobada: cupo entre créditos/canales, reverso antes del intento y cruce prepare/send sin deadlock.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    $exitCode = 1;
} finally {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    foreach ($processes as $process) {
        if ($process->isRunning()) {
            $process->stop();
        }
    }
    if ($barrier !== null) {
        foreach (glob($barrier.'/*') as $file) {
            unlink($file);
        }
        rmdir($barrier);
    }
}
exit($exitCode ?? 0);
