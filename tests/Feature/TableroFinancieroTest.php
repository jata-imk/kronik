<?php

use App\Enums\ActivityEvent;
use App\Models\Activity;
use App\Models\CreditoLectura;
use App\Models\CreditoPago;
use App\Services\Credito\ConsultaCartera;
use App\Services\Credito\PagoService;
use App\Services\Credito\ProyeccionCreditoService;
use App\Services\FechaEmpresa;
use App\Support\Decimal as D;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

test('contrato sin politica conserva desembolso autorizado y registra lectura no disponible sin defaults', function () {
    [$actor, $solicitud, , , $data] = prepararDesembolsoQa($this);
    $this->post(route('solicitudes.desembolso.store', $solicitud), $data)->assertSessionHasNoErrors();
    expect(CreditoLectura::sole()->estado)->toBe('no_disponible')->and(CreditoLectura::sole()->capital)->toBeNull();
    $this->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $p) => $p
        ->where('finanzas.cobertura.disponible', false)->where('finanzas.saldos', null));
});

test('diagnostico detecta obligaciones alteradas sin escrituras y reconstruccion las repara', function () {
    [, $credito] = creditoPagableQa($this);
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $fila = DB::table('credito_lectura_obligaciones')->first();
    DB::table('credito_lectura_obligaciones')->where('id', $fila->id)->update(['total' => '0.01']);
    $this->artisan('creditos:proyectar', ['--diagnosticar' => true])->assertFailed();
    expect((string) DB::table('credito_lectura_obligaciones')->where('id', $fila->id)->value('total'))->toBe('0.01');
    $this->artisan('creditos:proyectar', ['--reconstruir' => true])->assertSuccessful();
    $this->artisan('creditos:proyectar', ['--diagnosticar' => true])->assertSuccessful();
});

test('pago y reverso mantienen proyecciones cobros y corte corregido sin escribir al consultar', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $service = app(PagoService::class);
    $data['previa_hash'] = $service->previa($credito, $data, $actor)['previa_hash'];
    $pago = $service->registrar($credito, $data, $actor);
    $corte = app(FechaEmpresa::class)->hoy()->toDateString();
    $lectura = CreditoLectura::where('credito_id', $credito->id)->where('corte', $corte)->sole();
    expect($lectura->capital)->toBe($service->consultar($credito)['resumen']['capital_insoluto']);
    $scope = ['scope' => 'current', 'sucursal_id' => $credito->sucursal_id, 'requiereSucursal' => false];
    $cobros = app(ConsultaCartera::class)->cobros($scope, $corte, $corte);
    expect($cobros['total'])->toBe('500.00')->and(D::add($cobros['capital'], $lectura->capital, 2))->toBe($credito->capital_inicial);
    $conteos = [CreditoLectura::count(), $credito->movimientos()->count(), Activity::count()];
    $this->actingAs($actor)->get(route('dashboard', ['desde' => $corte, 'hasta' => $corte, 'detalle' => 'capital']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('finanzas.cobertura.disponible', true)
            ->where('finanzas.cobros.total', '500.00')->where('finanzas.saldos.capital', $lectura->capital)
            ->where('detalleFinanciero.total', 1));
    expect([CreditoLectura::count(), $credito->movimientos()->count(), Activity::count()])->toBe($conteos);
    $this->travel(2)->days();
    $service->reversar($credito, $pago, ['motivo' => 'Corrección sintética reservada.', 'idempotency_key' => (string) str()->uuid()], $actor);
    expect(app(ConsultaCartera::class)->cobros($scope, $corte, $corte)['total'])->toBe('0.00');
    expect(CreditoLectura::whereDate('corte', app(FechaEmpresa::class)->hoy())->sole()->capital)->toBe($credito->capital_inicial);
});

test('fallar proyeccion revierte pago movimientos cronograma y auditoria juntos', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $service = app(PagoService::class);
    $data['previa_hash'] = $service->previa($credito, $data, $actor)['previa_hash'];
    $antes = [$credito->movimientos()->count(), $credito->cronogramas()->count(), Activity::count(), CreditoLectura::count()];
    $evento = 'eloquent.saving: '.CreditoLectura::class;
    Event::listen($evento, fn () => throw new RuntimeException('Fallo sintético de almacenamiento.'));
    try {
        expect(fn () => $service->registrar($credito, $data, $actor))->toThrow(RuntimeException::class);
    } finally {
        Event::forget($evento);
    }
    expect(CreditoPago::count())->toBe(0)
        ->and([$credito->movimientos()->count(), $credito->cronogramas()->count(), Activity::count(), CreditoLectura::count()])->toBe($antes);
    $service->registrar($credito, $data, $actor);
    $service->registrar($credito, $data, $actor);
    expect(CreditoPago::count())->toBe(1)->and(Activity::where('event', ActivityEvent::CreditPaymentRecorded->value)->count())->toBe(1);
});

test('lectura faltante o fuente modificada no se muestra como cero ni se repara mediante GET', function () {
    [$actor, $credito] = creditoPagableQa($this);
    $this->actingAs($actor)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $p) => $p
        ->where('finanzas.cobertura.disponible', false)->where('finanzas.saldos', null));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    DB::table('creditos')->where('id', $credito->id)->update(['condiciones_hash' => str_repeat('0', 64)]);
    $antes = CreditoLectura::count();
    $this->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $p) => $p
        ->where('finanzas.cobertura.disponible', false)->where('finanzas.saldos', null));
    expect(CreditoLectura::count())->toBe($antes);
});

test('fallo al publicar obligaciones restaura tambien el encabezado de la proyeccion', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $service = app(PagoService::class);
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $antes = CreditoLectura::orderBy('id')->get()->map->getRawOriginal()->toArray();
    $obligaciones = DB::table('credito_lectura_obligaciones')->orderBy('id')->get()->toArray();
    $data['previa_hash'] = $service->previa($credito, $data, $actor)['previa_hash'];
    $fallar = true;
    DB::listen(function ($query) use (&$fallar) {
        if ($fallar && str_starts_with(strtolower($query->sql), 'insert into') && str_contains($query->sql, 'credito_lectura_obligaciones')) {
            throw new RuntimeException('Fallo sintético después de escribir obligaciones.');
        }
    });
    try {
        expect(fn () => $service->registrar($credito, $data, $actor))->toThrow(RuntimeException::class);
    } finally {
        $fallar = false;
    }
    expect(CreditoPago::count())->toBe(0)->and(CreditoLectura::orderBy('id')->get()->map->getRawOriginal()->toArray())->toBe($antes)
        ->and(DB::table('credito_lectura_obligaciones')->orderBy('id')->get()->toArray())->toEqual($obligaciones);
});

test('reconstruccion es equivalente al avance por intervalos y audita sin importes ni contexto inferido', function () {
    [$actor, $credito] = creditoPagableQa($this);
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $this->travel(1)->days();
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $actual = CreditoLectura::orderBy('corte')->get()->map->only(['corte', 'capital', 'vencido', 'estado_hash'])->toArray();
    $this->artisan('creditos:proyectar', ['--reconstruir' => true])->assertSuccessful();
    expect(CreditoLectura::orderBy('corte')->get()->map->only(['corte', 'capital', 'vencido', 'estado_hash'])->toArray())->toEqual($actual);
    $event = Activity::where('event', ActivityEvent::CreditProjectionsRebuilt->value)->sole();
    expect($event->causer_id)->toBeNull()->and($event->team_id)->toBeNull()->and($event->sucursal_id)->toBeNull()
        ->and($event->properties->get('result'))->toBe('completada');
    $conteos = [CreditoLectura::count(), Activity::count()];
    $this->artisan('creditos:proyectar', ['--diagnosticar' => true])->assertSuccessful();
    expect([CreditoLectura::count(), Activity::count()])->toBe($conteos);
});

test('tablero valida fechas corte y detalle en espanol y oculta finanzas sin permisos', function () {
    $actor = \App\Models\User::factory()->withPersonalTeam()->create();
    $this->actingAs($actor)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $p) => $p
        ->where('finanzas', null)->where('detalleFinanciero', null));
    foreach ([['corte' => 'incorrecto'], ['corte' => '2026-10-15'], ['detalle' => 'secreto'], ['desde' => ['dato' => 1]], ['desde' => '2026-11-10', 'hasta' => '2026-11-01']] as $data) {
        $r = $this->getJson(route('dashboard', $data))->assertUnprocessable();
        foreach (collect($r->json('errors'))->flatten() as $error) {
            expect($error)->not->toContain('validation.');
        }
    }
});

test('tablero limita la activa y limpia cifras al revocar permisos incluso en recarga parcial', function () {
    [$actor, $credito] = creditoPagableQa($this);
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $otra = \App\Models\Sucursal::create(['nombre' => 'Otra sucursal del tablero', 'clave' => 'TAB-OTRA', 'activa' => true]);
    $this->actingAs($actor)->get(route('dashboard', ['scope' => 'all', 'sucursal_id' => $otra->id]))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('finanzas.saldos.capital', '0.00')->where('finanzas.cobertura.creditos', 0));
    $this->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $p) => $p->where('finanzas.saldos.capital', $credito->capital_inicial));
    $actor->forceFill(['is_super_admin' => false])->save();
    $this->actingAs($actor->fresh())->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => \Inertia\Inertia::getVersion(), 'X-Inertia-Partial-Component' => 'Dashboard', 'X-Inertia-Partial-Data' => 'resumen'])
        ->get(route('dashboard'))->assertOk()->assertJsonPath('props.finanzas', null)
        ->assertJsonPath('props.detalleFinanciero', null)->assertJsonPath('props.accesos.creditos', false)->assertJsonCount(0, 'props.etapas');
});

test('reverso posterior corrige cierres afectados conservando capital anterior al pago', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $fechaPago = \Carbon\CarbonImmutable::parse($data['fecha_pago'], 'America/Mexico_City');
    $cierrePrevio = $fechaPago->startOfMonth()->subMonth()->endOfMonth()->toDateString();
    $cierrePago = $fechaPago->endOfMonth()->toDateString();
    $pagos = app(PagoService::class);
    $data['previa_hash'] = $pagos->previa($credito, $data, $actor)['previa_hash'];
    $pago = $pagos->registrar($credito, $data, $actor);
    $this->travelTo($fechaPago->startOfMonth()->addMonth()->addDays(2)->setTime(12, 0));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $enero = CreditoLectura::where('corte', $cierrePrevio)->sole();
    $febrero = CreditoLectura::where('corte', $cierrePago)->sole();
    expect($enero->capital)->toBe($credito->capital_inicial)->and(D::compare($febrero->capital, $credito->capital_inicial))->toBe(-1);
    $pagos->reversar($credito, $pago, ['motivo' => 'Corrección sintética posterior al cierre.', 'idempotency_key' => (string) str()->uuid()], $actor);
    expect(CreditoLectura::where('corte', $cierrePago)->sole()->capital)->toBe($credito->capital_inicial)
        ->and(CreditoLectura::where('corte', $cierrePrevio)->sole()->estado_hash)->toBe($enero->estado_hash);
    app(ProyeccionCreditoService::class)->diagnosticar($credito);
});
