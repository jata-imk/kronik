<?php

use App\Enums\ActivityEvent;
use App\Models\Activity;
use App\Services\Credito\DesembolsoService;
use App\Services\Credito\PagoService;
use App\Services\ProductoVersionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\BulletScenario;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2027-01-01 12:00:00', 'America/Mexico_City'));
    activarEmpresaPrueba();
    Storage::fake('local');
    Queue::fake();
    config(['documentos.disk' => 'local']);
    BulletScenario::habilitar();
    BulletScenario::pdfSintetico();
});

function originarBulletQa($test, string $politica, string $base = 'obligacion_vencida'): array
{
    $actor = actingAsSuperAdmin();
    $test->actingAs($actor);
    $version = BulletScenario::producto($actor, $politica, $base);
    $solicitud = BulletScenario::aprobar(BulletScenario::solicitud($actor, $version), $actor);
    $paquete = BulletScenario::formalizar($solicitud, $actor);
    $data = BulletScenario::desembolso($solicitud, $paquete);
    $credito = app(DesembolsoService::class)->registrar($solicitud->fresh(), $data, $actor);

    return [$actor, $credito, $version, $paquete, $data];
}

function operacionBulletQa($test, $credito, string $tipo, string $importe, string $fecha): array
{
    $data = ['operacion' => $tipo, 'fecha_pago' => $fecha, 'importe' => $importe, 'referencia' => 'BUL-'.Str::uuid(),
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $preview = $test->postJson(route($tipo === 'pago' ? 'creditos.pagos.preview' : 'creditos.anticipos.preview', $credito), $data)
        ->assertOk()->assertJsonMissingPath('snapshot')->json();
    $data['previa_hash'] = $preview['previa_hash'];

    return [$data, $preview];
}

test('ciclo QA conserva política tabla CAT firma crédito anticipos liquidación y auditoría', function (string $politica, string $base) {
    [$actor, $credito, $version, $paquete, $desembolso] = originarBulletQa($this, $politica, $base);
    $tabla = $paquete->snapshot['tabla'];
    expect(array_column(array_slice($tabla['tabla'], 1), 'pago_total'))->toBe($politica === 'periodicos' ? ['248.00', '12224.00'] : ['0.00', '12472.00'])
        ->and($tabla['politica_interes_bullet'])->toBe($politica)
        // Independent quadratic/root calculation of the two monthly cash flows.
        ->and($tabla['cat'])->toBe($politica === 'periodicos' ? '26.3' : '26.0')
        ->and($credito->condiciones['producto'])->toBe($paquete->snapshot['producto'])
        ->and($credito->cronogramas()->sole()->snapshot)->toBe($tabla)
        ->and($paquete->formalizacion->snapshot['paquete_hash'])->toBe($paquete->snapshot_hash);
    expect(app(DesembolsoService::class)->registrar($credito->solicitud, $desembolso, $actor)->id)->toBe($credito->id);
    $original = $credito->cronogramas()->sole()->getRawOriginal();
    $hashes = [$credito->condiciones_hash, $paquete->snapshot_hash, $version->snapshot_hash];
    $antes = [Activity::count(), $credito->movimientos()->count()];
    $this->travelTo(CarbonImmutable::parse('2027-01-16 12:00:00', 'America/Mexico_City'));
    $resumen = app(PagoService::class)->consultar($credito)['resumen'];
    expect($resumen)->toMatchArray(['interes_devengado_pendiente' => '120.00', 'interes_no_exigible' => '120.00', 'exigible' => '0.00']);
    [$data, $preview] = operacionBulletQa($this, $credito, 'prepago', '3120.00', '2027-01-16');
    expect($preview['despues'])->toMatchArray(['capital_insoluto' => '8880.00', 'interes_no_exigible' => '120.00'])
        ->and([Activity::count(), $credito->movimientos()->count()])->toBe($antes);
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    expect($credito->pagos()->count())->toBe(1)->and($credito->cronogramas()->count())->toBe(2);
    $nueva = $credito->cronogramas()->where('version', 2)->sole()->snapshot;
    expect(array_column($nueva['tabla'], 'interes'))->toBe($politica === 'periodicos' ? ['214.72', '165.76'] : ['0.00', '380.48'])
        ->and(array_column($nueva['tabla'], 'fecha'))->toBe(['2027-02-01', '2027-03-01']);
    $this->travelTo(CarbonImmutable::parse('2027-01-20 12:00:00', 'America/Mexico_City'));
    [$liquidacion, $previa] = operacionBulletQa($this, $credito, 'liquidacion', '9023.68', '2027-01-20');
    expect($previa['importe'])->toBe('9023.68');
    $this->post(route('creditos.anticipos.store', $credito), $liquidacion)->assertSessionHasNoErrors();
    expect(app(PagoService::class)->consultar($credito)['resumen']['estado_operativo'])->toBe('liquidado');
    $pago = $credito->pagos()->latest('id')->firstOrFail();
    $reverso = ['motivo' => 'Corrección sintética de liquidación Bullet.', 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), $reverso)->assertSessionHasNoErrors();
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), $reverso)->assertSessionHasNoErrors();
    expect(app(PagoService::class)->consultar($credito)['resumen'])->toMatchArray(['estado_operativo' => 'activo', 'capital_insoluto' => '8880.00'])
        ->and($credito->cronogramas()->where('version', 1)->sole()->getRawOriginal())->toBe($original)
        ->and([$credito->fresh()->condiciones_hash, $paquete->fresh()->snapshot_hash, $version->fresh()->snapshot_hash])->toBe($hashes);
    // A later product version cannot reinterpret the signed loan.
    $copia = app(ProductoVersionService::class)->nuevaVersion($version->producto, $version, $actor->id);
    expect($copia->snapshot)->toBeNull()->and($credito->fresh()->condiciones['producto']['reglas']['politica_interes_bullet'])->toBe($politica);
    expect(Activity::where('event', ActivityEvent::CreditPaymentRecorded->value)->count())->toBe(2)
        ->and(Activity::where('event', ActivityEvent::CreditPaymentReversed->value)->count())->toBe(1);
    assertDomainAudit([ActivityEvent::CreditProductCreated->value, ActivityEvent::CreditProductActivated->value,
        ActivityEvent::CreditPaymentRecorded->value, ActivityEvent::CreditPaymentReversed->value], $actor, $credito->sucursal_id,
        ['9023.68', '3120.00', 'Corrección sintética de liquidación Bullet.', $data['referencia']]);
    $html = view('documentos.paquete-anexo', ['snapshot' => $paquete->snapshot])->render();
    expect($html)->toContain('conservar vencimiento', 'Base moratoria:', 'capital al vencimiento')->not->toContain('Aplicación: reducir pago');
})->with(['periodicos', 'al_vencimiento'])->with(['saldo_insoluto', 'capital_inicial', 'obligacion_vencida']);

test('originación real distingue interés vencido capital futuro y vencimiento final', function (string $politica) {
    [$actor, $credito] = originarBulletQa($this, $politica);
    $this->travelTo(CarbonImmutable::parse('2027-02-01 12:00:00', 'America/Mexico_City'));
    if ($politica === 'periodicos') {
        [$data] = operacionBulletQa($this, $credito, 'pago', '100.00', '2027-02-01');
        $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    } else {
        $this->postJson(route('creditos.pagos.preview', $credito), ['fecha_pago' => '2027-02-01', 'importe' => '100.00',
            'referencia' => 'SIN-EXIGIBLE', 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true])->assertUnprocessable();
    }
    $this->travelTo(CarbonImmutable::parse('2027-02-02 12:00:00', 'America/Mexico_City'));
    $resumen = app(PagoService::class)->consultar($credito)['resumen'];
    expect($resumen['capital_vencido'])->toBe('0.00')->and($resumen['interes_vencido'])->toBe($politica === 'periodicos' ? '148.00' : '0.00')
        ->and($resumen['dias_atraso'])->toBe($politica === 'periodicos' ? 1 : 0);
    $this->travelTo(CarbonImmutable::parse('2027-03-02 12:00:00', 'America/Mexico_City'));
    expect(app(PagoService::class)->consultar($credito)['resumen']['capital_vencido'])->toBe('12000.00');
})->with(['periodicos', 'al_vencimiento']);

test('el ciclo originado permite consulta separada de pago y no expone condiciones cifradas', function () {
    [$actor, $credito] = originarBulletQa($this, 'al_vencimiento');
    $lector = \App\Models\User::factory()->create(['current_team_id' => $actor->current_team_id,
        'current_sucursal_id' => $actor->current_sucursal_id, 'sucursal_principal_id' => $actor->current_sucursal_id]);
    $actor->currentTeam->users()->attach($lector);
    $lector->sucursales()->attach($actor->current_sucursal_id);
    grantTeamPermissions($lector, $actor->currentTeam, ['read clientes', 'read solicitudes', 'read creditos']);
    $counts = [$credito->pagos()->count(), $credito->cronogramas()->count(), Activity::count()];
    $this->actingAs($lector)->get(route('creditos.show', $credito))->assertOk()
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->missing('credito.condiciones')->where('puedePagar', false)->where('puedeAnticipar', false));
    $this->get(route('creditos.pagos.create', $credito))->assertForbidden();
    $this->get(route('creditos.anticipos.create', $credito))->assertForbidden();
    expect([$credito->pagos()->count(), $credito->cronogramas()->count(), Activity::count()])->toBe($counts);
});
