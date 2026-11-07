<?php

use App\Enums\ActivityEvent;
use App\Enums\ProductoVersionEstado;
use App\Enums\VersionMotorCredito;
use App\Models\Activity;
use App\Models\ProductoVersion;
use App\Services\Credito\CapacidadesProducto;
use App\Services\Credito\CatalogoCapacidadesCredito;
use App\Services\ProductoVersionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function productoBullet(string $politica = 'periodicos', string $base = 'obligacion_vencida'): array
{
    $data = productoPayload();
    $data['version']['reglas'] = [...$data['version']['reglas'],
        'metodos_amortizacion' => ['bullet'], 'politica_interes_bullet' => $politica,
        'base_ordinaria' => 'saldo_insoluto', 'base_moratoria' => $base, 'aplicacion_prepago' => 'conservar_fecha'];

    return $data;
}

test('guarda y copia ambas políticas con las tres bases explícitas sin activar Bullet', function (string $politica, string $base) {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor)->postJson(route('productos-crediticios.store'), productoBullet($politica, $base))->assertRedirect();
    $version = ProductoVersion::sole();
    $service = app(ProductoVersionService::class);
    $copia = $service->nuevaVersion($version->producto, $version, $actor->id);
    foreach ([$version, $copia] as $item) {
        expect($item->reglas->base_ordinaria)->toBe('saldo_insoluto')
            ->and($item->reglas->base_moratoria)->toBe($base)
            ->and($item->reglas->politica_interes_bullet)->toBe($politica)
            ->and($item->reglas->aplicacion_prepago)->toBe('conservar_fecha')
            ->and($item->snapshot)->toBeNull()->and($item->estado)->toBe(ProductoVersionEstado::Borrador)
            ->and(CapacidadesProducto::paraVersion($item)['problemas'])->toBe([]);
    }
    expect($version->fresh()->reglas->base_moratoria)->toBe($base);
    assertDomainAudit([ActivityEvent::CreditProductCreated->value, ActivityEvent::CreditProductVersioned->value], $actor, $actor->current_sucursal_id, ['politica_interes_bullet', 'obligacion_vencida']);
})->with(['periodicos', 'al_vencimiento'])->with(['saldo_insoluto', 'capital_inicial', 'obligacion_vencida']);

test('modificar solo condiciones Bullet se audita una vez y consultar no escribe', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $service = app(ProductoVersionService::class);
    $data = productoBullet();
    $producto = $service->crear($data, $actor->id);
    $version = $producto->versiones()->sole();
    $data['version']['reglas']['base_moratoria'] = 'capital_inicial';
    $data['version']['reglas']['politica_interes_bullet'] = 'al_vencimiento';
    $service->actualizar($producto, $version, $data);
    $service->actualizar($producto, $version, $data);
    expect(Activity::where('event', ActivityEvent::CreditProductUpdated->value)->count())->toBe(1);
    $eventos = Activity::count();
    $before = $version->fresh()->reglas->getAttributes();
    $this->get(route('productos-crediticios.index', ['producto_id' => $producto->id, 'version_id' => $version->id, 'modo' => 'consulta']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('productoSeleccionado.versionSeleccionada.id', $version->id)
            ->where('productoSeleccionado.versionSeleccionada.reglas.base_moratoria', 'capital_inicial')
            ->where('productoSeleccionado.versionSeleccionada.reglas.politica_interes_bullet', 'al_vencimiento'));
    expect(Activity::count())->toBe($eventos)->and($version->fresh()->reglas->getAttributes())->toBe($before);
    assertDomainAudit([ActivityEvent::CreditProductUpdated->value], $actor, $actor->current_sucursal_id, ['capital_inicial', 'al_vencimiento']);
});

test('payload incompatible falla en HTTP y servicio sin producto ni actividad', function (string $campo, mixed $valor) {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $data = productoBullet();
    data_set($data, 'version.reglas.'.$campo, $valor);
    $eventos = Activity::count();
    $response = $this->postJson(route('productos-crediticios.store'), $data)->assertUnprocessable();
    expect(json_encode($response->json('errors')))->not->toContain('validation.');
    expect(fn () => app(ProductoVersionService::class)->crear($data, $actor->id))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('productos_crediticios', 0);
    expect(Activity::count())->toBe($eventos);
})->with([
    ['politica_interes_bullet', null], ['politica_interes_bullet', 'diarios'],
    ['base_moratoria', null], ['base_moratoria', 'capital_vencido'], ['base_moratoria', 'igual_ordinario'],
    ['base_ordinaria', 'capital_inicial'], ['aplicacion_prepago', 'reducir_plazo'], ['aplicacion_prepago', 'reducir_pago'],
    ['metodos_amortizacion', ['bullet', 'capital_fijo']],
]);

test('Bullet activa programa usa y simula con condiciones congeladas compatibles', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $service = app(ProductoVersionService::class);
    $version = $service->crear(productoBullet(), $actor->id)->versiones()->sole();
    $before = Activity::count();
    $this->postJson(route('productos-crediticios.activar', $version), ['vigente_desde' => app(\App\Services\FechaEmpresa::class)->hoy()->toDateString()])->assertRedirect();
    $version->refresh();
    $snapshot = $version->getRawOriginal('snapshot');
    $hash = $version->snapshot_hash;
    $service->registrarUso($version, 'solicitud', 123);
    $this->postJson(route('productos-crediticios.simular', $version), ['monto' => '10000', 'periodicidad' => 'mensual', 'plazo' => 3, 'metodo' => 'bullet', 'fecha' => '2026-01-01'])
        ->assertOk()->assertJsonPath('politica_interes_bullet', 'periodicos');
    expect($version->fresh()->estado)->toBe(ProductoVersionEstado::Activa)->and($version->usos()->count())->toBe(1)
        ->and($version->getRawOriginal('snapshot'))->toBe($snapshot)->and($version->snapshot_hash)->toBe($hash)
        ->and(Activity::count())->toBe($before + 1);
    $copia = $service->nuevaVersion($version->producto, $version, $actor->id);
    $service->activar($copia, app(\App\Services\FechaEmpresa::class)->hoy()->addDay()->toDateString());
    expect($copia->fresh()->estado)->toBe(ProductoVersionEstado::Programada);
    $this->travel(2)->days();
    expect($service->activarProgramadas())->toBe(1)->and($service->activarProgramadas())->toBe(0)
        ->and($copia->fresh()->estado)->toBe(ProductoVersionEstado::Activa)
        ->and($version->fresh()->snapshot_hash)->toBe($hash);
});

test('los métodos previos rechazan condiciones de Bullet y mantienen sus bases históricas', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    foreach (['saldo_insoluto', 'capital_inicial', 'obligacion_vencida'] as $base) {
        $data = productoPayload();
        $data['version']['reglas']['base_moratoria'] = $base;
        $this->postJson(route('productos-crediticios.store'), $data)->assertUnprocessable();
    }
    $data = productoPayload();
    $data['version']['reglas']['politica_interes_bullet'] = 'periodicos';
    $this->postJson(route('productos-crediticios.store'), $data)->assertUnprocessable();
    $malformado = productoPayload()['version'];
    $malformado['reglas']['metodos_amortizacion'] = 'capital_fijo';
    expect(CapacidadesProducto::desdeCondiciones($malformado)['problemas'])->not->toBeEmpty();
    foreach ([new \App\Http\Requests\GuardarSolicitudRequest, new \App\Http\Requests\SimularCreditoRequest] as $request) {
        $validator = \Illuminate\Support\Facades\Validator::make(['metodo' => 'bullet'], ['metodo' => $request->rules()['metodo']]);
        expect($validator->fails())->toBeFalse();
    }
    expect(CatalogoCapacidadesCredito::valores('metodos_amortizacion', VersionMotorCredito::Historico, true))->not->toContain('bullet')
        ->and(array_column(CatalogoCapacidadesCredito::paraVue()['configuracion']['opciones']['metodos_amortizacion'], 'value'))->toContain('bullet')
        ->and(array_column(CatalogoCapacidadesCredito::paraVue(true)['configuracion']['opciones']['metodos_amortizacion'], 'value'))->toContain('bullet');
});

test('la migración nullable y la consulta no rellenan condiciones ni hashes históricos', function () {
    $actor = actingAsSuperAdmin();
    $service = app(ProductoVersionService::class);
    $version = $service->crear(productoPayload(), $actor->id)->versiones()->sole();
    DB::table('producto_version_reglas')->where('producto_version_id', $version->id)->update(['base_ordinaria' => null, 'politica_interes_bullet' => null]);
    $version = $service->activar($version->fresh(), '2026-01-01');
    $snapshot = $version->snapshot;
    unset($snapshot['reglas']['base_ordinaria'], $snapshot['reglas']['politica_interes_bullet']);
    DB::table('producto_versiones')->where('id', $version->id)->update(['snapshot' => json_encode($snapshot), 'snapshot_hash' => hash('sha256', json_encode($snapshot))]);
    $version = $version->fresh();
    $antes = $version->getRawOriginal('snapshot');
    $hash = $version->snapshot_hash;
    $this->actingAs($actor)->get(route('productos-crediticios.index', ['producto_id' => $version->producto_crediticio_id, 'version_id' => $version->id]))->assertOk();
    expect($version->fresh()->getRawOriginal('snapshot'))->toBe($antes)->and($version->fresh()->snapshot_hash)->toBe($hash)
        ->and($version->fresh()->reglas->base_ordinaria)->toBeNull();
    $data = productoPayload();
    $data['version']['reglas']['base_ordinaria'] = 'saldo_insoluto';
    $this->putJson(route('productos-crediticios.update', [$version->producto_crediticio_id, $version->id]), $data)->assertUnprocessable();
});
