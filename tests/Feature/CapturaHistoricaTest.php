<?php

use App\Enums\ActivityEvent;
use App\Enums\EstadoCapturaHistorica;
use App\Models\Activity;
use App\Models\Credito;
use App\Models\CreditoPago;
use App\Services\ActivityLogService;
use App\Services\Credito\CapturaHistoricaService;
use App\Services\Credito\PagoService;
use App\Services\FechaOriginacion;
use App\Services\ProductoVersionService;
use App\Services\SolicitudService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BulletScenario;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2027-02-05 12:00:00', 'America/Mexico_City'));
    activarEmpresaPrueba();
    $disk = 'historico-test-'.getmypid();
    Storage::fake($disk);
    Queue::fake();
    config(['documentos.disk' => $disk]);
    BulletScenario::habilitar();
    BulletScenario::pdfSintetico();
});

function prepararCreditoHistorico($test, string $politica = 'periodicos', string $base = 'obligacion_vencida', string $gracia = 'efectiva', string $intereses = 'ambos', bool|int|string $confirmacion = true, ?string $fechaEfectiva = null): array
{
    $fechaEfectiva ??= '2027-01-01';
    $actor = actingAsSuperAdmin();
    $test->actingAs($actor);
    $version = BulletScenario::producto($actor, $politica, $base, activar: false);
    $version->update(['dias_gracia_mora' => 3, 'politica_mora' => compact('gracia', 'intereses')]);
    $test->post(route('productos-crediticios.activar', $version), ['vigente_desde' => $fechaEfectiva, 'confirmacion_historica' => true])->assertSessionHasNoErrors();
    $version->refresh();
    $solicitud = BulletScenario::solicitud($actor, $version);
    $solicitud = app(SolicitudService::class)->guardar($solicitud, ['lock_version' => $solicitud->lock_version, 'fecha_estimada' => $fechaEfectiva], $actor);
    $solicitud = BulletScenario::aprobar($solicitud, $actor);
    $paquete = BulletScenario::formalizar($solicitud, $actor);
    $data = [...BulletScenario::desembolso($solicitud, $paquete), 'fecha_desembolso' => $fechaEfectiva, 'confirmacion_historica' => $confirmacion];
    $test->post(route('solicitudes.desembolso.store', $solicitud), $data)->assertStatus(302)->assertSessionHasNoErrors();
    $credito = $solicitud->credito()->sole();

    return [$actor, $credito, $version, $paquete, $data];
}

function registrarPagoHistorico($test, Credito $credito, string $fecha, string $importe, string $operacion = 'pago'): array
{
    $data = ['operacion' => $operacion, 'fecha_pago' => $fecha, 'importe' => $importe, 'referencia' => 'HIST-'.Str::uuid(),
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $ruta = $operacion === 'pago' ? 'creditos.pagos' : 'creditos.anticipos';
    $previa = $test->postJson(route($ruta.'.preview', $credito), $data)->assertOk()->json();
    $data['previa_hash'] = $previa['previa_hash'];
    $test->post(route($ruta.'.store', $credito), $data)->assertStatus(302)->assertSessionHasNoErrors();

    return [$data, $previa];
}

test('recorrido histórico conserva momentos reales tabla firma producto y registro único', function (string $politica) {
    [$actor, $credito, $version, $paquete, $data] = prepararCreditoHistorico($this, $politica);
    expect($version->vigente_desde->toDateString())->toBe('2027-01-01')
        ->and($version->activada_en->toDateString())->toBe('2027-02-05')
        ->and($paquete->formalizacion->snapshot['fecha_firma'])->toBe('2027-02-05')
        ->and($credito->fecha_desembolso->toDateString())->toBe('2027-01-01')
        ->and($credito->created_at->toDateString())->toBe('2027-02-05')
        ->and($credito->desembolso->created_at->toDateString())->toBe('2027-02-05')
        ->and($credito->condiciones['producto'])->toBe($paquete->snapshot['producto'])
        ->and($credito->cronogramas()->sole()->snapshot)->toBe($paquete->snapshot['tabla']);
    $this->post(route('solicitudes.desembolso.store', $credito->solicitud), $data)->assertSessionHasNoErrors();
    expect($credito->capturaHistorica()->count())->toBe(1)->and($credito->movimientos()->count())->toBe(1);
    $service = app(CapturaHistoricaService::class);
    expect($service->estado($credito))->toBe(EstadoCapturaHistorica::Pendiente)->and($service->permiteAvisosFinancieros($credito))->toBeFalse()
        ->and(Credito::conHistoriaCompleta()->count())->toBe(0);
    $counts = [Activity::count(), $credito->movimientos()->count()];
    $this->get(route('creditos.show', $credito))->assertInertia(fn (Assert $page) => $page
        ->where('capturaHistorica.estado', 'pendiente')->where('puedeCompletarCaptura', true)->missing('credito.captura_historica'));
    $this->get(route('creditos.index'))->assertInertia(fn (Assert $page) => $page->where('creditos.data.0.captura_historica.estado', 'pendiente'));
    expect([Activity::count(), $credito->movimientos()->count()])->toBe($counts);
    $evento = Activity::where('event', ActivityEvent::CreditDisbursed->value)->sole();
    expect($evento->created_at->toDateString())->toBe('2027-02-05')->and($evento->properties['state'])->toBe('captura_historica_pendiente');
    assertDomainAudit([ActivityEvent::CreditDisbursed->value], $actor, $credito->sucursal_id, [$data['referencia'], '12000.00', '2027-01-01']);
})->with(['periodicos', 'al_vencimiento']);

test('pagos cronológicos reconstruyen gracia y mora con oráculos independientes', function (string $base, string $gracia, string $intereses) {
    [, $credito] = prepararCreditoHistorico($this, base: $base, gracia: $gracia, intereses: $intereses);
    // 12000 × 24 × 31 / 36000 = 248 al 01/02; abono 100 deja 148 vencidos.
    [$data] = registrarPagoHistorico($this, $credito, '2027-02-01', '100.00');
    $resumen = app(PagoService::class)->consultar($credito)['resumen'];
    // Al 05/02: A cobra un día, B cuatro. Base global 12000 o remanente 148.
    $mora = $base === 'obligacion_vencida' ? ($gracia === 'efectiva' ? '0.15' : '0.59') : ($gracia === 'efectiva' ? '12.00' : '48.00');
    $ordinario = $intereses === 'ambos' ? '32.00' : ($gracia === 'efectiva' ? '24.00' : '0.00');
    expect($resumen['capital_insoluto'])->toBe('12000.00')->and($resumen['capital_vencido'])->toBe('0.00')
        ->and($resumen['interes_vencido'])->toBe('148.00')->and($resumen['interes_no_exigible'])->toBe($ordinario)
        ->and($resumen['exigible'])->toBe(bcadd('148.00', $mora, 2));
    $pago = $credito->pagos()->sole();
    expect($pago->created_at->toDateString())->toBe('2027-02-05')->and($pago->fecha_efectiva->toDateString())->toBe('2027-02-01');
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $this->postJson(route('creditos.pagos.preview', $credito), [...$data, 'fecha_pago' => '2027-01-31'])
        ->assertUnprocessable()->assertJsonFragment(['No puedes registrar este pago con esa fecha porque el crédito tiene movimientos posteriores.']);
    expect($credito->pagos()->count())->toBe(1);
})->with(['saldo_insoluto', 'capital_inicial', 'obligacion_vencida'])->with(['efectiva', 'retroactiva'])->with(['ambos', 'sustituye']);

test('al vencimiento conserva devengado no exigible y anticipo liquidación y reverso históricos', function (string $politica) {
    [, $credito, , $paquete] = prepararCreditoHistorico($this, $politica);
    $hashes = [$credito->condiciones_hash, $paquete->snapshot_hash, $credito->cronogramas()->sole()->snapshot_hash];
    registrarPagoHistorico($this, $credito, '2027-01-16', '3120.00', 'prepago');
    // 120 conservados + 4 días × 5.92 = 143.68; capital 8880.
    [$data, $previa] = registrarPagoHistorico($this, $credito, '2027-01-20', '9023.68', 'liquidacion');
    expect($previa['importe'])->toBe('9023.68')->and(app(PagoService::class)->consultar($credito)['resumen']['estado_operativo'])->toBe('liquidado');
    $ultimo = $credito->pagos()->latest('id')->first();
    $reverso = ['motivo' => 'Corrección reservada del ejemplo histórico.', 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $this->post(route('creditos.pagos.reverse', [$credito, $ultimo]), $reverso)->assertSessionHasNoErrors();
    $this->post(route('creditos.pagos.reverse', [$credito, $ultimo]), $reverso)->assertSessionHasNoErrors();
    expect($credito->pagos()->count())->toBe(3)
        ->and([$credito->fresh()->condiciones_hash, $paquete->fresh()->snapshot_hash, $credito->cronogramas()->where('version', 1)->sole()->snapshot_hash])->toBe($hashes);
    if ($politica === 'al_vencimiento') {
        $resumen = app(PagoService::class)->consultar($credito)['resumen'];
        // 120 + 20 días × 5.92, sin vencimiento de interés ni capital.
        expect($resumen['interes_no_exigible'])->toBe('238.40')->and($resumen['exigible'])->toBe('0.00')->and($resumen['dias_atraso'])->toBe(0);
    }
    $this->post(route('creditos.pagos.reverse', [$credito, $credito->pagos()->oldest('id')->first()]), [...$reverso, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('pago');
})->with(['periodicos', 'al_vencimiento']);

test('pagar durante gracia detiene mora y conserva el límite de parcialidad B con sustitución', function (string $gracia, string $intereses) {
    [, $credito] = prepararCreditoHistorico($this, gracia: $gracia, intereses: $intereses);
    if ($gracia === 'retroactiva' && $intereses === 'sustituye') {
        $this->postJson(route('creditos.pagos.preview', $credito), ['fecha_pago' => '2027-02-03', 'importe' => '100', 'referencia' => 'PARCIAL-GRACIA',
            'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true])->assertUnprocessable();
        expect($credito->pagos()->count())->toBe(0);
    }
    registrarPagoHistorico($this, $credito, '2027-02-03', '248.00');
    expect(app(PagoService::class)->consultar($credito)['resumen'])->toMatchArray(['exigible' => '0.00', 'interes_vencido' => '0.00', 'dias_atraso' => 0, 'interes_no_exigible' => '32.00']);
})->with(['efectiva', 'retroactiva'])->with(['ambos', 'sustituye']);

test('completar captura no modifica finanzas y es idempotente aun sin pagos y con atraso', function () {
    [$actor, $credito] = prepararCreditoHistorico($this);
    $antes = [app(PagoService::class)->consultar($credito), $credito->getRawOriginal(), $credito->movimientos()->get()->toArray()];
    $ruta = route('creditos.captura-historica.completar', $credito);
    $this->post($ruta, [])->assertSessionHasErrors(['confirmacion_qa', 'confirmacion_historial_completo']);
    $data = ['confirmacion_qa' => true, 'confirmacion_historial_completo' => true];
    $this->post($ruta, $data)->assertSessionHasNoErrors();
    $this->post($ruta, $data)->assertSessionHasNoErrors();
    expect([app(PagoService::class)->consultar($credito), $credito->fresh()->getRawOriginal(), $credito->movimientos()->get()->toArray()])->toBe($antes)
        ->and(app(CapturaHistoricaService::class)->estado($credito->fresh()))->toBe(EstadoCapturaHistorica::Completa)
        ->and(app(CapturaHistoricaService::class)->permiteAvisosFinancieros($credito))->toBeTrue()
        ->and(Credito::conHistoriaCompleta()->count())->toBe(1);
    $evento = Activity::where('event', ActivityEvent::CreditHistoricalCaptureCompleted->value)->sole();
    expect($evento->causer_id)->toBe($actor->id)->and($evento->subject_id)->toBe($credito->id)
        ->and($evento->team_id)->toBe($actor->current_team_id)->and($evento->sucursal_id)->toBe($credito->sucursal_id);
    expect(fn () => $credito->capturaHistorica()->sole()->update(['created_at' => now()->subDay()]))->toThrow(ValidationException::class);
    expect(fn () => $credito->capturaHistorica()->sole()->delete())->toThrow(ValidationException::class);
    assertDomainAudit([ActivityEvent::CreditHistoricalCaptureCompleted->value], $actor, $credito->sucursal_id, ['12000.00', '2027-01-01']);
});

test('fallo de auditoría revierte el cierre sin alterar captura ni libro', function () {
    [$actor, $credito] = prepararCreditoHistorico($this);
    $this->mock(ActivityLogService::class)->shouldReceive('log')->once()->andThrow(new RuntimeException('Auditoría no disponible'));
    expect(fn () => app(CapturaHistoricaService::class)->completar($credito, $actor))->toThrow(RuntimeException::class);
    expect($credito->capturaHistorica()->sole()->completada_en)->toBeNull()
        ->and(Activity::where('event', ActivityEvent::CreditHistoricalCaptureCompleted->value)->count())->toBe(0)
        ->and(app(CapturaHistoricaService::class)->permiteAvisosFinancieros($credito))->toBeFalse();
});

test('lectura y cierre respetan permisos contexto de equipo sucursal y puerta QA', function () {
    [$actor, $credito] = prepararCreditoHistorico($this);
    $lector = App\Models\User::factory()->create(['current_team_id' => $actor->current_team_id,
        'current_sucursal_id' => $actor->current_sucursal_id, 'sucursal_principal_id' => $actor->current_sucursal_id]);
    $actor->currentTeam->users()->attach($lector);
    $lector->sucursales()->attach($actor->current_sucursal_id);
    grantTeamPermissions($lector, $actor->currentTeam, ['read clientes', 'read solicitudes', 'read creditos']);
    $ruta = route('creditos.captura-historica.completar', $credito);
    $data = ['confirmacion_qa' => true, 'confirmacion_historial_completo' => true];
    $this->actingAs($lector)->get(route('creditos.show', $credito))->assertInertia(fn (Assert $page) => $page->where('puedeCompletarCaptura', false));
    $this->post($ruta, $data)->assertForbidden();
    grantTeamPermissions($lector, $actor->currentTeam, ['pay creditos']);
    $otro = App\Models\Team::factory()->create();
    $lector->forceFill(['current_team_id' => $otro->id])->save();
    setPermissionsTeamId($otro->id);
    $this->post($ruta, $data)->assertForbidden();
    $this->actingAs($actor);
    setPermissionsTeamId($actor->current_team_id);
    $actor->forceFill(['current_sucursal_id' => null])->save();
    $this->post($ruta, $data)->assertSessionHasErrors('captura_historica');
    $actor->forceFill(['current_sucursal_id' => $credito->sucursal_id])->save();
    config(['originacion.pagos_qa_habilitados' => false]);
    $this->post($ruta, $data)->assertSessionHasErrors('captura_historica');
    expect($credito->capturaHistorica()->sole()->completada_en)->toBeNull();
});

test('vigencia histórica exige confirmación habilitación y no permite editar condiciones congeladas', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $version = BulletScenario::producto($actor, 'periodicos', activar: false);
    $ruta = route('productos-crediticios.activar', $version);
    $this->post($ruta, ['vigente_desde' => '2027-01-01'])->assertSessionHasErrors('confirmacion_historica');
    expect(fn () => app(ProductoVersionService::class)->activar($version, '2027-01-01'))->toThrow(ValidationException::class);
    config(['originacion.desembolsos_qa_habilitados' => false]);
    expect(fn () => app(ProductoVersionService::class)->activar($version, '2027-01-01', true))->toThrow(ValidationException::class);
    config(['originacion.desembolsos_qa_habilitados' => true]);
    app(ProductoVersionService::class)->activar($version, '2027-01-01', true);
    expect(fn () => $version->fresh()->update(['vigente_desde' => '2026-01-01']))->toThrow(ValidationException::class);
    expect(app(FechaOriginacion::class)->problema('2026-12-31', $version->fresh()))->toContain('vigencia')
        ->and(app(FechaOriginacion::class)->problema('2027-02-30', $version->fresh()))->toContain('válida');
    foreach (session('errors')?->all() ?? [] as $error) {
        expect($error)->not->toContain('validation.');
    }
});

test('confirmaciones booleanas equivalentes conservan idempotencia histórica', function (bool|int|string $confirmacion) {
    [, $credito, , , $data] = prepararCreditoHistorico($this, confirmacion: $confirmacion);
    $this->post(route('solicitudes.desembolso.store', $credito->solicitud), [...$data, 'confirmacion_historica' => true])
        ->assertRedirect(route('creditos.show', $credito))->assertSessionHasNoErrors();
    expect($credito->desembolso()->count())->toBe(1)->and($credito->capturaHistorica()->count())->toBe(1);
})->with([true, 1, '1']);

test('otra versión con igual vigencia rechaza sin retirar ni alterar la activa', function () {
    [$actor, , $version] = prepararCreditoHistorico($this);
    $service = app(ProductoVersionService::class);
    $copia = $service->nuevaVersion($version->producto, $version, $actor->id);
    $antes = Activity::count();
    $this->post(route('productos-crediticios.activar', $copia), ['vigente_desde' => '2027-01-01', 'confirmacion_historica' => true])
        ->assertSessionHasErrors('vigente_desde');
    expect($version->fresh()->estado->value)->toBe('activa')->and($copia->fresh()->estado->value)->toBe('borrador')
        ->and(Activity::count())->toBe($antes);
});

test('fechas de pago inválidas se rechazan sin efectos y la consulta a hoy no impide historia admisible', function (string $fecha) {
    [, $credito] = prepararCreditoHistorico($this);
    $antes = [CreditoPago::count(), $credito->movimientos()->count(), Activity::count()];
    app(PagoService::class)->consultar($credito);
    $response = $this->postJson(route('creditos.pagos.preview', $credito), ['fecha_pago' => $fecha, 'importe' => '100', 'referencia' => 'FECHA-INVALIDA',
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true])->assertUnprocessable();
    expect($response->getContent())->not->toContain('validation.');
    expect([CreditoPago::count(), $credito->movimientos()->count(), Activity::count()])->toBe($antes);
})->with(['2026-12-31', '2027-02-06', '2027-02-30']);

test('consultar historias extensas conserva evidencia y no agrega consultas por día', function (int $dias) {
    $fecha = app(App\Services\FechaEmpresa::class)->hoy()->subDays($dias)->toDateString();
    [, $credito] = prepararCreditoHistorico($this, fechaEfectiva: $fecha);
    $antes = [Activity::count(), $credito->movimientos()->count(), $credito->pagos()->count(), $credito->cronogramas()->count()];
    Illuminate\Support\Facades\DB::enableQueryLog();
    Illuminate\Support\Facades\DB::flushQueryLog();
    $memoria = memory_get_usage();
    $inicio = hrtime(true);
    $situacion = app(PagoService::class)->consultar($credito);
    $milisegundos = (hrtime(true) - $inicio) / 1e6;
    $consultas = count(Illuminate\Support\Facades\DB::getQueryLog());
    Illuminate\Support\Facades\DB::disableQueryLog();
    expect($situacion['error'])->toBeNull()->and($consultas)->toBeLessThanOrEqual(8)
        ->and([Activity::count(), $credito->movimientos()->count(), $credito->pagos()->count(), $credito->cronogramas()->count()])->toBe($antes);
    file_put_contents(storage_path('logs/medicion-historica-'.getmypid().'.jsonl'), json_encode([
        'php' => PHP_VERSION, 'os' => PHP_OS_FAMILY, 'db' => 'SQLite :memory:', 'dias' => $dias,
        'consultas' => $consultas, 'ms' => round($milisegundos, 3), 'memoria_incremental' => memory_get_usage() - $memoria,
    ], JSON_THROW_ON_ERROR)."\n", FILE_APPEND);
})->with([30, 365, 1825]);
