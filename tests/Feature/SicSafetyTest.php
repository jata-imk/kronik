<?php

use App\Models\Cliente;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use App\Services\NavigationService;
use App\Services\SICs\ConsultaSicNoDisponible;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Database\Seeders\SicsSeeder;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function historicalSicQuery(Cliente $cliente, array $attributes = []): SicQuery
{
    return SicQuery::create(array_merge([
        'cliente_id' => $cliente->id,
        'sic_id' => Sic::where('clave', 'circulo-credito')->firstOrFail()->id,
        'sic_api_id' => SicApi::where('clave', 'fico_score_v2')->firstOrFail()->id,
        'fecha_consulta' => now(),
        'status' => 'success',
        'response_data' => ['secreto' => 'NO_EXPONER_RESPUESTA'],
        'mensaje_error' => 'NO_EXPONER_ERROR',
    ], $attributes));
}

test('SIC history uses its own permission in addition to client access', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo('read clientes');
    $cliente = Cliente::factory()->create();

    $this->actingAs($user)->get(route('clientes.historial-crediticio.index'))->assertForbidden();
    $this->get(route('clientes.historial-crediticio.show', $cliente))->assertForbidden();
    $this->get(route('circulo-credito.create'))->assertForbidden();
    $this->post(route('circulo-credito.store'), ['cliente' => $cliente->id])->assertForbidden();

    $user->givePermissionTo('read historial-crediticio');
    $this->get(route('clientes.historial-crediticio.index'))->assertOk();
    $this->get(route('clientes.historial-crediticio.show', $cliente))->assertOk();
});

test('SIC history is paginated deterministically and never serializes provider payloads', function () {
    $this->seed(SicsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    $first = historicalSicQuery($cliente);
    $last = historicalSicQuery($cliente);
    $other = historicalSicQuery(Cliente::factory()->create());

    $response = $this->actingAs($user)->get(route('clientes.historial-crediticio.show', $cliente));
    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('HistorialCrediticio/Index')
        ->where('consultas.total', 2)
        ->where('consultas.data.0.id', $last->id)
        ->where('consultas.data.1.id', $first->id)
        ->missing('consultas.data.0.response_data')
        ->missing('consultas.data.0.mensaje_error')
        ->missing('consultas.data.0.cliente.ingresos_mensuales'));
    expect($first->toArray())->not->toHaveKeys(['response_data', 'mensaje_error']);

    $this->get(route('clientes.historial-crediticio.index', ['estado' => 'error']))
        ->assertInertia(fn (Assert $page) => $page->where('consultas.total', 0));
    $this->get(route('clientes.historial-crediticio.index', ['estado' => 'inventado']))
        ->assertSessionHasErrors(['estado' => 'Selecciona un estado de consulta válido.']);
});

test('even superadmin cannot invoke an unvalidated SIC integration', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    $this->actingAs($user)->get(route('circulo-credito.create'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->missing('clientes')->where('motivoNoDisponible', ConsultaSicNoDisponible::MENSAJE));
    foreach (['fico_score_v2', 'fintech', 'rc_fico_score', 'unsupported'] as $api) {
        $this->post(route('circulo-credito.store', $cliente), ['api' => $api])
            ->assertSessionHasErrors(['sic' => ConsultaSicNoDisponible::MENSAJE]);
    }
    $this->assertDatabaseCount('sic_queries', 0);
});

test('legacy services and repositories fail closed without recording fictitious queries', function (string $folder, string $prefix) {
    $cliente = Cliente::factory()->create();
    $base = 'App\\Services\\SICs\\CirculoDeCredito\\'.$folder.'\\'.$prefix;
    expect(fn () => app($base.'Service')->getReporte())->toThrow(ValidationException::class);
    expect(fn () => app($base.'Repository')->consultaScore($cliente))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('sic_queries', 0);
})->with([['FicoScorev2', 'FicoScorev2'], ['FintechScore', 'FintechScore'], ['RCFicoScore', 'RCFicoScore']]);

test('client deletion preserves SIC evidence', function () {
    $this->seed(SicsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    $query = historicalSicQuery($cliente);
    $this->actingAs($user)->delete(route('clientes.destroy', $cliente))->assertSessionHasErrors('cliente');
    $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    $this->assertDatabaseHas('sic_queries', ['id' => $query->id]);
});

test('navigation exposes product catalogue independently from administration and hides SIC without permission', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read productos-crediticios']);
    $menu = collect(app(NavigationService::class)->forUser($user))->pluck('items')->flatten(1);
    expect($menu->pluck('to')->all())->toContain('productos-crediticios.index', 'clientes.index')
        ->not->toContain('admin.dashboard', 'clientes.historial-crediticio.index', 'sakai');
    $this->actingAs($user)->get(route('productos-crediticios.index'))->assertOk();

    $user->forceFill(['is_super_admin' => true])->save();
    $menu = collect(app(NavigationService::class)->forUser($user))->pluck('items')->flatten(1);
    expect($menu->pluck('to')->all())->toContain('admin.dashboard', 'clientes.historial-crediticio.index');
});
