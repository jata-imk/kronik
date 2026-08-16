<?php

use App\Enums\ProductoVersionEstado;
use App\Models\ConceptoComision;
use App\Services\ProductoVersionService;
use Inertia\Testing\AssertableInertia as Assert;

function productoPayload(array $version = []): array
{
    return [
        'clave' => 'CS-001',
        'nombre' => 'Crédito Simple Esencial',
        'descripcion' => 'Producto de prueba',
        'version' => [
            'monto_minimo' => '5000.00',
            'monto_maximo' => '100000.00',
            'tasa_ordinaria_anual' => '36.00',
            'tasa_moratoria_anual' => '72.00',
            'dias_gracia_mora' => 3,
            'cat_aplica' => true,
            'cat_no_aplica_motivo' => null,
            'vigente_desde' => null,
            'periodicidades' => [['periodicidad' => 'mensual', 'plazo_minimo' => 3, 'plazo_maximo' => 24, 'plazo_predeterminado' => 12]],
            'reglas' => ['metodos_amortizacion' => ['cuota_nivelada', 'capital_fijo'], 'permite_prepago_parcial' => true, 'permite_liquidacion_anticipada' => true, 'monto_minimo_prepago' => '500.00', 'aplicacion_prepago' => 'reducir_plazo'],
            'comisiones' => [],
            ...$version,
        ],
    ];
}

test('super admin puede crear y consultar productos globales', function () {
    $user = actingAsSuperAdmin();

    $this->actingAs($user)->post(route('productos-crediticios.store'), productoPayload())->assertRedirect();

    $this->assertDatabaseHas('productos_crediticios', ['clave' => 'CS-001']);
    $this->assertDatabaseHas('producto_versiones', ['numero' => 1, 'estado' => 'borrador']);
    $this->actingAs($user)->get(route('productos-crediticios.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('ProductosCrediticios/Index')->has('productos', 1));
});

test('activar congela la versión y versionar conserva el histórico', function () {
    $user = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoPayload(), $user->id);
    $version = $producto->versiones()->first();

    $this->actingAs($user)->post(route('productos-crediticios.activar', $version), ['vigente_desde' => today()->toDateString()])->assertSessionHasNoErrors()->assertRedirect();
    $version->refresh();

    expect($version->estado)->toBe(ProductoVersionEstado::Activa)
        ->and($version->snapshot_hash)->toHaveLength(64);
    expect(fn () => $version->update(['monto_maximo' => '999999']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'inmutables');

    $this->actingAs($user)->post(route('productos-crediticios.versionar', [$producto, $version]))->assertRedirect();
    expect($producto->versiones()->count())->toBe(2)
        ->and($producto->versiones()->latest('numero')->first()->estado)->toBe(ProductoVersionEstado::Borrador);
});

test('una versión utilizada tampoco admite edición destructiva', function () {
    $user = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoPayload(), $user->id);
    $version = $producto->versiones()->first();
    app(ProductoVersionService::class)->registrarUso($version, 'solicitudes', 42);

    $payload = productoPayload(['monto_maximo' => '120000.00']);
    $this->actingAs($user)->put(route('productos-crediticios.update', [$producto, $version]), $payload)
        ->assertSessionHasErrors(['version']);
});

test('validación visible es clara y rechaza comisión tardía junto con mora', function () {
    $user = actingAsSuperAdmin();
    $concepto = ConceptoComision::create(['clave' => 'PAGO_TARDIO', 'nombre' => 'Pago tardío', 'activo' => true]);
    $payload = productoPayload([
        'monto_minimo' => null,
        'comisiones' => [[
            'concepto_comision_id' => $concepto->id,
            'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica',
            'momento_cobro' => 'evento', 'obligatoria' => true, 'incluye_cat' => false,
        ]],
    ]);

    $response = $this->actingAs($user)->post(route('productos-crediticios.store'), $payload);
    $response->assertSessionHasErrors(['version.monto_minimo', 'version.comisiones.0.concepto_comision_id']);
    expect(session('errors')->get('version.monto_minimo')[0])->not->toContain('validation.')
        ->and(session('errors')->get('version.comisiones.0.concepto_comision_id')[0])->toContain('tasa moratoria');
    expect(collect(session('errors')->all())->implode(' '))->not->toContain('validation.')
        ->and(session('errors')->get('version.monto_maximo')[0])->toBe('El monto máximo debe ser mayor o igual que el monto mínimo.');
});

test('simulador produce tabla sin residuos y CAT informativo', function () {
    $user = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoPayload(), $user->id);
    $version = $producto->versiones()->first();

    $response = $this->actingAs($user)->postJson(route('productos-crediticios.simular', $version), [
        'monto' => '15000', 'periodicidad' => 'mensual', 'plazo' => 12,
        'metodo' => 'cuota_nivelada', 'fecha' => '2026-01-31',
    ])->assertOk();

    $response->assertJsonPath('tabla.12.saldo', '0.00')
        ->assertJsonPath('tabla.0.tipo', 'disposicion')
        ->assertJsonPath('tabla.1.dias', 28);
    expect($response->json('cat'))->not->toBeNull();
});

test('simulador hace visible apertura y comisión porcentual de cada pago', function () {
    $user = actingAsSuperAdmin();
    $apertura = ConceptoComision::create(['clave' => 'APERTURA-TEST', 'nombre' => 'Apertura', 'activo' => true]);
    $administracion = ConceptoComision::create(['clave' => 'ADMIN-TEST', 'nombre' => 'Administración', 'activo' => true]);
    $producto = app(ProductoVersionService::class)->crear(productoPayload([
        'monto_minimo' => '5000.00',
        'comisiones' => [
            [
                'concepto_comision_id' => $apertura->id, 'tipo_importe' => 'fijo', 'importe' => '500',
                'base_calculo' => 'no_aplica', 'momento_cobro' => 'inicio', 'modalidad_cobro' => 'pago_separado',
                'obligatoria' => true, 'incluye_cat' => true,
            ],
            [
                'concepto_comision_id' => $administracion->id, 'tipo_importe' => 'porcentaje', 'importe' => '1',
                'base_calculo' => 'monto_credito', 'momento_cobro' => 'cada_pago', 'modalidad_cobro' => null,
                'obligatoria' => true, 'incluye_cat' => true,
            ],
        ],
    ]), $user->id);

    $response = $this->actingAs($user)->postJson(route('productos-crediticios.simular', $producto->versiones()->first()), [
        'monto' => '5000', 'periodicidad' => 'mensual', 'plazo' => 12,
        'metodo' => 'cuota_nivelada', 'fecha' => '2026-08-16',
    ])->assertOk();

    $response
        ->assertJsonPath('tabla.0.comisiones', '500.00')
        ->assertJsonPath('tabla.1.saldo_inicial', '5000.00')
        ->assertJsonPath('tabla.1.capital', '348.64')
        ->assertJsonPath('tabla.1.interes', '155.00')
        ->assertJsonPath('tabla.1.comisiones', '50.00')
        ->assertJsonPath('tabla.1.pago_total', '553.64')
        ->assertJsonPath('tabla.1.saldo_final', '4651.36')
        ->assertJsonPath('tabla.12.saldo_final', '0.00')
        ->assertJsonPath('total_intereses', '1043.68')
        ->assertJsonPath('total_comisiones', '1100.00')
        ->assertJsonPath('total_pagar', '7143.68');
});

test('comisión financiada consume el monto máximo del producto', function () {
    $user = actingAsSuperAdmin();
    $apertura = ConceptoComision::create(['clave' => 'APERTURA-FIN', 'nombre' => 'Apertura financiada', 'activo' => true]);
    $producto = app(ProductoVersionService::class)->crear(productoPayload([
        'monto_minimo' => '1000.00', 'monto_maximo' => '5000.00',
        'comisiones' => [[
            'concepto_comision_id' => $apertura->id, 'tipo_importe' => 'fijo', 'importe' => '500',
            'base_calculo' => 'no_aplica', 'momento_cobro' => 'inicio', 'modalidad_cobro' => 'financiada',
            'obligatoria' => true, 'incluye_cat' => true,
        ]],
    ]), $user->id);

    $this->actingAs($user)->postJson(route('productos-crediticios.simular', $producto->versiones()->first()), [
        'monto' => '4800', 'periodicidad' => 'mensual', 'plazo' => 12,
        'metodo' => 'cuota_nivelada', 'fecha' => '2026-08-16',
    ])->assertUnprocessable()->assertJsonValidationErrors('monto');
});

test('simulador distingue las tres modalidades de comisión inicial', function (string $modalidad, string $saldo, string $entregado, string $flujoNeto, string $pagoInicial) {
    $user = actingAsSuperAdmin();
    $concepto = ConceptoComision::create([
        'clave' => 'INICIAL-'.strtoupper($modalidad),
        'nombre' => 'Comisión inicial',
        'activo' => true,
    ]);
    $producto = app(ProductoVersionService::class)->crear(productoPayload([
        'comisiones' => [[
            'concepto_comision_id' => $concepto->id,
            'tipo_importe' => 'fijo',
            'importe' => '500',
            'base_calculo' => 'no_aplica',
            'momento_cobro' => 'inicio',
            'modalidad_cobro' => $modalidad,
            'obligatoria' => true,
            'incluye_cat' => true,
        ]],
    ]), $user->id);

    $response = $this->actingAs($user)->postJson(route('productos-crediticios.simular', $producto->versiones()->first()), [
        'monto' => '5000', 'periodicidad' => 'mensual', 'plazo' => 12,
        'metodo' => 'cuota_nivelada', 'fecha' => '2026-08-16',
    ])->assertOk();

    $response
        ->assertJsonPath('escenario.saldo_financiado', $saldo)
        ->assertJsonPath('escenario.efectivo_entregado', $entregado)
        ->assertJsonPath('escenario.flujo_neto_inicial', $flujoNeto)
        ->assertJsonPath('tabla.0.pago_total', $pagoInicial)
        ->assertJsonPath('tabla.0.comisiones_detalle.0.modalidad', $modalidad)
        ->assertJsonPath('tabla.12.saldo_final', '0.00');
})->with([
    'pago separado' => ['pago_separado', '5000.00', '5000.00', '4500.00', '500.00'],
    'descuento de disposición' => ['descuento_desembolso', '5000.00', '4500.00', '4500.00', '0.00'],
    'financiada' => ['financiada', '5500.00', '5000.00', '5000.00', '0.00'],
]);

test('clave duplicada de concepto devuelve mensaje legible en español', function () {
    $user = actingAsSuperAdmin();
    ConceptoComision::create(['clave' => 'DUPLICADA', 'nombre' => 'Primera', 'activo' => true]);

    $this->actingAs($user)->post(route('conceptos-comision.store'), [
        'clave' => 'DUPLICADA', 'nombre' => 'Segunda', 'descripcion' => null, 'referencia_reco' => null,
        'es_oficial_reco' => false, 'revisado' => false, 'activo' => true,
    ])->assertSessionHasErrors(['clave' => 'La clave del concepto de comisión ya está en uso.']);
});
