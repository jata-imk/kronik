<?php

use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Services\Credito\CatInformativoService;
use App\Services\Credito\CalendarioCrediticioService;
use App\Services\Credito\ConsultaCartera;
use App\Services\Credito\SimuladorCreditoSimple;
use App\Services\ProductoVersionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
    $this->actor = actingAsSuperAdmin();
    $this->actingAs($this->actor);
    $this->plantilla = config('calendario-crediticio.cnbv_2026');
});

test('la simulación anterior reproduce las fechas congeladas tras otra publicación', function () {
    $calendario = app(CalendarioCrediticioService::class);
    $primera = $calendario->guardar(null, ['configuracion' => $this->plantilla], $this->actor);
    $calendario->publicar($primera, 1, $this->actor);
    $datos = productoPayload();
    $datos['version']['reglas']['ajuste_dia_inhabil'] = 'siguiente_habil';
    $datos['version']['periodicidades'] = [['periodicidad' => 'semanal', 'plazo_minimo' => 1, 'plazo_maximo' => 5, 'plazo_predeterminado' => 1]];
    $productos = app(ProductoVersionService::class);
    $producto = $productos->crear($datos, $this->actor->id)->versiones()->sole();
    unset($datos['version']['reglas']['ajuste_dia_inhabil']);
    $productos->actualizar($producto->producto, $producto, $datos);
    $producto->refresh();
    expect($producto->reglas->ajuste_dia_inhabil)->toBe('siguiente_habil');
    $simulador = app(SimuladorCreditoSimple::class);
    $parametros = [$producto, '12000', PeriodicidadCredito::Semanal, 1, MetodoAmortizacion::CapitalFijo, CarbonImmutable::parse('2026-11-07')];
    $original = $simulador->simular(...$parametros);
    expect($original['tabla'][1]['fecha'])->toBe('2026-11-17');

    $excepcion = ['fecha' => '2026-11-14', 'nombre' => 'Apertura de prueba', 'habil' => true];
    $segunda = $calendario->guardar(null, ['configuracion' => [...$this->plantilla, 'excepciones' => [$excepcion]]], $this->actor);
    $calendario->publicar($segunda, 1, $this->actor);

    expect($simulador->simular(...$parametros)['tabla'][1]['fecha'])->toBe('2026-11-14');
    expect($simulador->simular(...$parametros, calendarioCongelado: $original['calendario_crediticio'], reproducir: true))->toBe($original);

    $parametros[5] = CarbonImmutable::parse('2026-09-06', 'America/Santiago');
    $escenario = $simulador->simular(...$parametros);
    expect($escenario['tabla'][1])->toMatchArray(['fecha' => '2026-09-14', 'dias' => 8, 'interes' => '96.00'])
        ->and($escenario['cat'])->toBe('43.1');

    $usuario = \App\Models\User::factory()->withPersonalTeam()->create();
    $equipo = $usuario->ownedTeams()->sole();
    $usuario->forceFill(['current_team_id' => $equipo->id])->save();
    grantTeamPermissions($usuario, $equipo, ['simulate productos-crediticios']);
    $this->actingAs($usuario)->postJson(route('productos-crediticios.simular', $producto), [
        'monto' => '12000', 'periodicidad' => 'semanal', 'plazo' => 1, 'metodo' => 'capital_fijo', 'fecha' => '2026-11-07',
    ])->assertOk()->assertJsonPath('tabla.1.fecha_original', '2026-11-14')
        ->assertJsonMissingPath('calendario_crediticio');
});

test('el CAT de pago único conserva el empate decimal al redondear', function () {
    $cat = app(CatInformativoService::class);
    expect($cat->calcular('10000.00', ['10005.00'], PeriodicidadCredito::Mensual, 360))->toBe('0.1')
        ->and($cat->calcular('10000.00', ['10004.99'], PeriodicidadCredito::Mensual, 360))->toBe('0.0');
});

test('una política inválida identifica el campo de vencimientos en días inhábiles', function () {
    $datos = productoPayload();
    $datos['version']['reglas']['ajuste_dia_inhabil'] = 'no_valida';

    $this->post(route('productos-crediticios.store'), $datos)->assertSessionHasErrors([
        'version.reglas.ajuste_dia_inhabil' => 'El valor seleccionado para vencimientos en días inhábiles no es válido.',
    ]);
});

test('el contrato y desembolso Bullet conservan la fecha aprobada y su proyección', function (string $politica) {
    $this->travelTo(CarbonImmutable::parse('2026-09-14 12:00:00', 'America/Mexico_City'));
    activarEmpresaPrueba();
    \Illuminate\Support\Facades\Storage::fake('calendario_prueba');
    \Illuminate\Support\Facades\Queue::fake();
    config(['documentos.disk' => 'calendario_prueba']);
    \Tests\Support\BulletScenario::habilitar();
    \Tests\Support\BulletScenario::pdfSintetico();

    $calendario = app(CalendarioCrediticioService::class);
    $primera = $calendario->guardar(null, ['configuracion' => $this->plantilla], $this->actor);
    $calendario->publicar($primera, 1, $this->actor);
    $producto = \Tests\Support\BulletScenario::producto($this->actor, $politica, ajuste: 'siguiente_habil');
    $solicitud = \Tests\Support\BulletScenario::aprobar(\Tests\Support\BulletScenario::solicitud($this->actor, $producto), $this->actor);

    $excepcion = ['fecha' => '2026-11-14', 'nombre' => 'Apertura posterior', 'habil' => true];
    $segunda = $calendario->guardar(null, ['configuracion' => [...$this->plantilla, 'excepciones' => [$excepcion]]], $this->actor);
    $calendario->publicar($segunda, 1, $this->actor);

    $paquete = \Tests\Support\BulletScenario::formalizar($solicitud, $this->actor);
    $tabla = $paquete->snapshot['tabla'];
    expect($tabla['tabla'][2]['fecha_original'])->toBe('2026-11-14')
        ->and($tabla['tabla'][2]['fecha'])->toBe('2026-11-17')
        ->and($tabla['calendario_crediticio']['version_id'])->toBe($primera->id);
    expect(view('documentos.paquete-anexo', ['snapshot' => $paquete->snapshot])->render())
        ->toContain('Calendario crediticio, versión', '2026-11-17', 'Original: 2026-11-14');
    $entrada = \Tests\Support\BulletScenario::desembolso($solicitud, $paquete);
    $credito = app(\App\Services\Credito\DesembolsoService::class)->registrar($solicitud->fresh(), $entrada, $this->actor);
    expect($credito->cronogramas()->sole()->snapshot)->toBe($tabla);
    $this->assertDatabaseHas('credito_lecturas', ['credito_id' => $credito->id, 'cronograma_version' => 1]);

    $this->travelTo(CarbonImmutable::parse('2026-09-29 12:00', 'America/Mexico_City'));
    $pago = app(\App\Services\Credito\PagoService::class);
    $anticipo = ['operacion' => 'prepago', 'fecha_pago' => '2026-09-29', 'importe' => '3120.00', 'referencia' => 'ANT-QA',
        'idempotency_key' => (string) \Illuminate\Support\Str::uuid(), 'confirmacion_qa' => true];
    $previa = $pago->previa($credito, $anticipo, $this->actor);
    $registrado = $pago->registrar($credito, [...$anticipo, 'previa_hash' => $previa['previa_hash']], $this->actor);
    expect($pago->registrar($credito, [...$anticipo, 'previa_hash' => $previa['previa_hash']], $this->actor)->id)->toBe($registrado->id);
    $nuevaTabla = $credito->cronogramas()->where('version', 2)->sole()->snapshot;
    expect(end($nuevaTabla['tabla']))->toMatchArray(['fecha' => '2026-11-17', 'fecha_original' => '2026-11-14']);
    expect($nuevaTabla['calendario_crediticio'])->toBe($tabla['calendario_crediticio']);
    $reverso = $pago->reversar($credito, $registrado, ['motivo' => 'Corrección QA', 'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        'confirmacion_qa' => true], $this->actor);
    $this->assertDatabaseHas('credito_lecturas', ['credito_id' => $credito->id, 'cronograma_version' => 3]);
    expect($credito->cronogramas()->where('version', 1)->sole()->snapshot)->toBe($tabla);
    $proyecciones = app(\App\Services\Credito\ProyeccionCreditoService::class);
    if (DB::connection()->getDriverName() === 'sqlite') {
        DB::table('credito_pagos')->where('id', $reverso->id)->update(['fecha_efectiva' => '2026-09-29 00:00:00']);
    }
    $proyecciones->sincronizar($credito, reconstruir: true);
    $proyecciones->diagnosticar($credito);
    $alcance = ['scope' => 'current', 'sucursal_id' => $credito->sucursal_id, 'requiereSucursal' => false];
    expect(app(ConsultaCartera::class)->cobertura($alcance, '2026-09-29')['disponible'])->toBeTrue();

    $liquidacion = [...$anticipo, 'operacion' => 'liquidacion', 'importe' => '12120.00', 'referencia' => 'LIQ-QA',
        'idempotency_key' => (string) \Illuminate\Support\Str::uuid()];
    $previa = $pago->previa($credito, $liquidacion, $this->actor);
    $pago->registrar($credito, [...$liquidacion, 'previa_hash' => $previa['previa_hash']], $this->actor);
    expect($pago->consultar($credito)['resumen']['estado_operativo'])->toBe('liquidado')
        ->and($credito->cronogramas()->where('version', 4)->sole()->snapshot['calendario_crediticio'])->toBe($tabla['calendario_crediticio']);
})->with(['periodicos', 'al_vencimiento']);
