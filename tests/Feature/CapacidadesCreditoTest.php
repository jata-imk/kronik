<?php

use App\Enums\VersionMotorCredito;
use App\Models\Activity;
use App\Services\Credito\CapacidadesProducto;
use App\Services\Credito\CatalogoCapacidadesCredito;
use App\Services\ProductoVersionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

test('el catálogo conserva únicamente las capacidades actuales y exige compatibilidad explícita', function () {
    foreach (VersionMotorCredito::cases() as $motor) {
        $opciones = CatalogoCapacidadesCredito::paraMotor($motor);
        expect(array_column($opciones['metodos_amortizacion'], 'value'))->toBe(['cuota_nivelada', 'capital_fijo'])
            ->and(array_column($opciones['periodicidades'], 'value'))->toBe(['semanal', 'quincenal', 'mensual'])
            ->and(array_column($opciones['convenciones_interes'], 'value'))->toBe(['dias_reales_360']);
    }
    expect(CatalogoCapacidadesCredito::catalogo()['periodicidades'][1]['ayuda'])->toContain('cada 15 días');
    $condiciones = productoPayload()['version'];
    $condiciones['reglas']['metodos_amortizacion'] = ['capital_fijo', 'cuota_nivelada'];
    expect(array_column(CapacidadesProducto::desdeCondiciones($condiciones)['opciones']['metodos_amortizacion'], 'value'))->toBe(['capital_fijo', 'cuota_nivelada']);
});

test('las opciones contractuales usan snapshot y no cambian la evidencia ni las otras versiones', function () {
    $actor = actingAsSuperAdmin();
    $service = app(ProductoVersionService::class);
    $producto = $service->crear(productoPayload(), $actor->id);
    $version = $service->activar($producto->versiones()->first(), '2026-01-01');
    $antes = $version->getRawOriginal('snapshot');
    $hash = $version->snapshot_hash;
    DB::table('producto_version_reglas')->where('producto_version_id', $version->id)->update(['metodos_amortizacion' => json_encode(['capital_fijo'])]);
    expect(array_column(CapacidadesProducto::paraVersion($version->fresh())['opciones']['metodos_amortizacion'], 'value'))->toBe(['cuota_nivelada', 'capital_fijo']);
    $data = productoPayload();
    $data['clave'] = 'OTRO';
    $data['version']['reglas']['metodos_amortizacion'] = ['capital_fijo'];
    $otro = $service->crear($data, $actor->id)->versiones()->first();
    expect(array_column(CapacidadesProducto::paraVersion($otro)['opciones']['metodos_amortizacion'], 'value'))->toBe(['capital_fijo'])
        ->and($version->fresh()->getRawOriginal('snapshot'))->toBe($antes)
        ->and($version->fresh()->snapshot_hash)->toBe($hash);
    $this->actingAs($actor)->postJson(route('productos-crediticios.simular', $version), ['monto' => '10000', 'periodicidad' => 'mensual', 'plazo' => 3, 'metodo' => 'cuota_nivelada', 'fecha' => '2026-01-01'])->assertOk();
});

test('la presentación histórica conserva motor implícito y bloquea valores explícitos no soportados', function () {
    $condiciones = productoPayload()['version'];
    expect(CapacidadesProducto::desdeCondiciones($condiciones)['motor_calculo_version'])->toBe(1);
    $condiciones['motor_calculo_version'] = 2;
    $condiciones['reglas']['convencion_interes'] = null;
    $resultado = CapacidadesProducto::desdeCondiciones($condiciones);
    expect($resultado['problemas'])->not->toBeEmpty()->and($resultado['opciones']['metodos_amortizacion'])->toBe([]);
    expect($condiciones['reglas']['convencion_interes'])->toBeNull();
});

test('el prepago presenta solo la aplicación contractual si está permitido', function (bool $permitido, string $aplicacion) {
    $condiciones = productoPayload()['version'];
    $condiciones['reglas']['permite_prepago_parcial'] = $permitido;
    $condiciones['reglas']['aplicacion_prepago'] = $aplicacion;
    expect(array_column(CapacidadesProducto::desdeCondiciones($condiciones)['opciones']['aplicaciones_prepago'], 'value'))->toBe($permitido ? [$aplicacion] : []);
})->with([[true, 'reducir_plazo'], [true, 'reducir_pago'], [false, 'reducir_plazo']]);

test('las llamadas internas rechazan capacidades futuras sin persistencia ni actividad', function (string $dimension, mixed $valor) {
    $actor = actingAsSuperAdmin();
    $data = productoPayload();
    data_set($data, 'version.'.$dimension, $valor);
    $eventos = Activity::count();
    expect(fn () => app(ProductoVersionService::class)->crear($data, $actor->id))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('productos_crediticios', 0);
    expect(Activity::count())->toBe($eventos);
})->with([
    ['reglas.metodos_amortizacion', ['desconocido']],
    ['periodicidades.0.periodicidad', 'catorcenal'],
    ['reglas.aplicacion_prepago', 'otro'],
    ['reglas.convencion_interes', '30e_360'],
]);

test('Inertia publica capacidades derivadas fuera del snapshot y consultar no emite actividad', function () {
    $actor = actingAsSuperAdmin();
    app(ProductoVersionService::class)->crear(productoPayload(), $actor->id);
    \App\Models\ConceptoComision::create(['clave' => 'CONSULTA', 'nombre' => 'Comisión de prueba', 'activo' => true]);
    $eventos = Activity::count();
    $this->actingAs($actor)->get(route('productos-crediticios.index'))->assertInertia(fn (Assert $page) => $page
        ->has('capacidadesCredito.catalogo.metodos_amortizacion', 3)
        ->has('conceptosComision', 1)
        ->where('productos.0.versiones.0.capacidades.motor_calculo_version', 2)
        ->where('productos.0.versiones.0.snapshot', null));
    expect(Activity::count())->toBe($eventos);
});

test('el rechazo HTTP de un método futuro identifica el campo en español', function () {
    $actor = actingAsSuperAdmin();
    $data = productoPayload();
    $data['version']['reglas']['metodos_amortizacion'] = ['desconocido'];
    $respuesta = $this->actingAs($actor)->postJson(route('productos-crediticios.store'), $data)->assertUnprocessable();
    $mensaje = $respuesta->json('errors')['version.reglas.metodos_amortizacion.0'][0];
    expect($mensaje)->toContain('método de amortización')->not->toContain('validation.');
    $this->assertDatabaseCount('productos_crediticios', 0);
});
