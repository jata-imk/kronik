<?php

use App\Models\Activity;
use App\Models\EmpresaConfiguracion;
use App\Models\ProductoVersion;
use App\Services\ProductoVersionService;
use Inertia\Testing\AssertableInertia as Assert;

test('predeterminados de producto respetan cero y ausencia sin crear empresa ni actividad', function (array $parametros, int $esperado) {
    $actor = actingAsSuperAdmin();
    if ($parametros !== ['sin_empresa']) {
        EmpresaConfiguracion::create(['singleton_key' => 'default', 'estatus' => 'borrador', 'parametros_operativos' => $parametros]);
    }
    $empresas = EmpresaConfiguracion::count();
    $actividad = Activity::count();
    $this->actingAs($actor)->get(route('productos-crediticios.index'))
        ->assertInertia(fn (Assert $page) => $page->where('nuevoProductoDefaults', ['dias_gracia_mora' => $esperado])
            ->missing('configuracion')->missing('integraciones'));
    expect(EmpresaConfiguracion::count())->toBe($empresas)->and(Activity::count())->toBe($actividad);
})->with([
    'sin empresa' => [['sin_empresa'], 3],
    'sin clave' => [[], 3],
    'nulo' => [['dias_gracia_default' => null], 3],
    'cero' => [['dias_gracia_default' => 0], 0],
    'configurado' => [['dias_gracia_default' => 8], 8],
]);

test('lector de productos obtiene solo predeterminado sin permiso de empresa ni escrituras', function () {
    $actor = actingAsSuperAdmin();
    grantTeamPermissions($actor, $actor->currentTeam, ['read productos-crediticios']);
    $actor->forceFill(['is_super_admin' => false])->save();
    EmpresaConfiguracion::create(['singleton_key' => 'default', 'estatus' => 'borrador', 'parametros_operativos' => ['dias_gracia_default' => 0],
        'integraciones' => ['circulo_credito_api_key' => 'PRIVATE-DEFAULT-SECRET']]);
    $this->actingAs($actor)->get(route('productos-crediticios.index'))
        ->assertInertia(fn (Assert $p) => $p->where('nuevoProductoDefaults.dias_gracia_mora', 0))
        ->assertDontSee('PRIVATE-DEFAULT-SECRET');
    $this->get(route('admin.configuracion-empresa.index'))->assertForbidden();
    $this->postJson(route('productos-crediticios.store'), productoPayload())->assertForbidden();
});

test('guardar predeterminado audita una vez y no cambia condiciones explícitas ni versiones congeladas', function () {
    clienteFiscalCatalogs();
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $service = app(ProductoVersionService::class);
    $producto = $service->crear(productoPayload(['dias_gracia_mora' => 7]), $actor->id);
    $version = $service->activar($producto->versiones()->sole(), app(\App\Services\FechaEmpresa::class)->hoy()->toDateString());
    $snapshot = $version->snapshot;
    $uso = $service->registrarUso($version, 'prueba', 123);
    $hash = $uso->snapshot_hash;
    $fields = ['tipo_persona' => 'fisica', 'moneda' => 'MXN', 'zona_horaria' => 'America/Mexico_City', 'pais_base' => 'MX',
        'estatus' => 'borrador', 'parametros_operativos' => ['dias_gracia_default' => 0]];
    $this->put(route('admin.configuracion-empresa.update'), $fields)->assertSessionHasNoErrors();
    $this->put(route('admin.configuracion-empresa.update'), $fields)->assertSessionHasNoErrors();
    expect(Activity::where('event', 'empresa.updated')->count())->toBe(1);
    assertDomainAudit(['empresa.updated'], $actor, $actor->current_sucursal_id);
    $copy = $service->nuevaVersion($producto, $version, $actor->id);
    expect($copy->dias_gracia_mora)->toBe(7)->and($version->fresh()->snapshot)->toBe($snapshot)
        ->and($uso->fresh()->snapshot_hash)->toBe($hash)
        ->and($service->predeterminadosNuevoProducto()['dias_gracia_mora'])->toBe(0);
    $this->post(route('productos-crediticios.store'), ['clave' => 'EXPLICITO', ...array_diff_key(productoPayload(['dias_gracia_mora' => 2]), ['clave' => true])])
        ->assertSessionHasNoErrors();
    expect(ProductoVersion::latest('id')->first()->dias_gracia_mora)->toBe(2);
});

test('predeterminado inválido se explica en español sin persistir cambios', function (mixed $valor) {
    clienteFiscalCatalogs();
    $actor = actingAsSuperAdmin();
    $response = $this->actingAs($actor)->put(route('admin.configuracion-empresa.update'), [
        'tipo_persona' => 'fisica', 'moneda' => 'MXN', 'zona_horaria' => 'America/Mexico_City', 'pais_base' => 'MX',
        'estatus' => 'borrador', 'parametros_operativos' => ['dias_gracia_default' => $valor],
    ])->assertSessionHasErrors('parametros_operativos.dias_gracia_default');
    expect($response->getSession()->get('errors')->first('parametros_operativos.dias_gracia_default'))
        ->toContain('días de gracia predeterminados')->not->toContain('validation.');
    expect(EmpresaConfiguracion::count())->toBe(0);
})->with([-1, 366, 'invalido', 1.5]);
