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
});

test('simulador produce tabla sin residuos y CAT informativo', function () {
    $user = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoPayload(), $user->id);
    $version = $producto->versiones()->first();

    $response = $this->actingAs($user)->postJson(route('productos-crediticios.simular', $version), [
        'monto' => '15000', 'periodicidad' => 'mensual', 'plazo' => 12,
        'metodo' => 'cuota_nivelada', 'fecha' => '2026-01-31',
    ])->assertOk();

    $response->assertJsonPath('tabla.11.saldo', '0.00')->assertJsonPath('tabla.0.dias', 28);
    expect($response->json('cat'))->not->toBeNull();
});
