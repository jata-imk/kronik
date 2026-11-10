<?php

use App\Models\Activity;
use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use App\Models\Sucursal;
use App\Rules\Rfc;
use Inertia\Testing\AssertableInertia as Assert;

test('web y API admiten ambos genéricos repetidos para física y moral sin relajar permisos', function (string $canal, string $tipo, string $rfc) {
    $catalogs = clienteFiscalCatalogs();
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $payload = clientePayload($catalogs, ['tipo_persona' => $tipo, 'rfc' => mb_strtolower(" $rfc "),
        'regimen_fiscal_id' => $catalogs[$tipo === 'fisica' ? 'regimenFisica' : 'regimenMoral']->id]);
    for ($i = 0; $i < 2; $i++) {
        $this->postJson(route($canal.'clientes.store'), $payload)->assertStatus($canal === 'api.' ? 201 : 302);
    }
    expect(Cliente::count())->toBe(2)->and(\App\Models\ClienteDatosFiscales::where('rfc', $rfc)->where('tipo_persona', $tipo)->count())->toBe(2);
    assertDomainAudit(['cliente.created'], $actor, $actor->current_sucursal_id, [$rfc, $payload['datos_fiscales']['curp']]);
    expect(Activity::where('event', 'cliente.created')->count())->toBe(2);
    $actor->forceFill(['is_super_admin' => false])->save();
    $this->postJson(route($canal.'clientes.store'), $payload)->assertForbidden();
    expect(Cliente::count())->toBe(2);
})->with(['', 'api.'])->with(['fisica', 'moral'])->with(Rfc::genericos());

test('edición parcial conserva tipo y admite reemplazar genérico sin duplicar actividad', function (string $canal, string $tipo) {
    $catalogs = clienteFiscalCatalogs();
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor)->postJson(route($canal.'clientes.store'), clientePayload($catalogs, [
        'tipo_persona' => $tipo, 'rfc' => 'XAXX010101000',
        'regimen_fiscal_id' => $catalogs[$tipo === 'fisica' ? 'regimenFisica' : 'regimenMoral']->id,
    ]))->assertStatus($canal === 'api.' ? 201 : 302);
    $cliente = Cliente::sole();
    $own = $tipo === 'fisica' ? 'GALA910418AB8' : 'KDE260101AB1';
    foreach ([$own, $own, 'XEXX010101000'] as $rfc) {
        $this->patchJson(route($canal.'clientes.update', $cliente), ['datos_fiscales' => ['rfc' => " $rfc "]])
            ->assertStatus($canal === 'api.' ? 200 : 302);
    }
    expect($cliente->fresh()->datosFiscales->rfc)->toBe('XEXX010101000')
        ->and($cliente->fresh()->datosFiscales->tipo_persona)->toBe($tipo)
        ->and(Activity::where('event', 'cliente.updated')->count())->toBe(2);
    assertDomainAudit(['cliente.created', 'cliente.updated'], $actor, $cliente->sucursal_id, [$own, 'XAXX010101000', 'XEXX010101000']);
    $snapshot = $cliente->fresh()->datosFiscales->toArray();
    $this->patchJson(route($canal.'clientes.update', $cliente), ['datos_fiscales' => ['rfc' => 'GALA910418AB9']])
        ->assertUnprocessable()->assertJsonValidationErrors('datos_fiscales.rfc');
    $this->patchJson(route($canal.'clientes.update', $cliente), ['datos_fiscales' => ['tipo_persona' => null, 'rfc' => 'XAXX010101000']])
        ->assertUnprocessable()->assertJsonValidationErrors('datos_fiscales.rfc');
    expect($cliente->fresh()->datosFiscales->toArray())->toBe($snapshot);
    $this->get(route('clientes.show', $cliente))->assertInertia(fn (Assert $p) => $p->where('readOnly', true)
        ->where('rfcsGenericosPermitidos', Rfc::genericos())->where('cliente.datos_fiscales.rfc', 'XEXX010101000'));
})->with(['', 'api.'])->with(['fisica', 'moral']);

test('el genérico no permite modificar clientes desde otra sucursal ni otro equipo', function () {
    $catalogs = clienteFiscalCatalogs();
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor)->postJson(route('clientes.store'), clientePayload($catalogs, ['rfc' => 'XAXX010101000']))->assertRedirect();
    $cliente = Cliente::sole();
    grantTeamPermissions($actor, $actor->currentTeam, ['read clientes', 'update clientes']);
    $actor->forceFill(['is_super_admin' => false])->save();
    $cliente->update(['sucursal_id' => Sucursal::factory()->create()->id]);
    $this->patchJson(route('api.clientes.update', $cliente), ['datos_fiscales' => ['rfc' => 'XEXX010101000']])->assertForbidden();
    $cliente->update(['sucursal_id' => $actor->current_sucursal_id]);
    $other = \App\Models\Team::forceCreate(['name' => 'Otro equipo', 'user_id' => $actor->id, 'personal_team' => false]);
    $actor->teams()->attach($other);
    $actor->forceFill(['current_team_id' => $other->id])->save();
    $this->patchJson(route('clientes.update', $cliente), ['datos_fiscales' => ['rfc' => 'XEXX010101000']])->assertForbidden();
    expect($cliente->fresh()->datosFiscales->rfc)->toBe('XAXX010101000')
        ->and(Activity::where('event', 'cliente.updated')->count())->toBe(0);
});

test('Empresa sigue rechazando ambos genéricos con mensaje español y sin guardar', function (string $rfc) {
    clienteFiscalCatalogs();
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor)->putJson(route('admin.configuracion-empresa.update'), [
        'tipo_persona' => 'fisica', 'rfc' => $rfc, 'moneda' => 'MXN', 'zona_horaria' => 'America/Mexico_City', 'pais_base' => 'MX', 'estatus' => 'borrador',
    ])->assertUnprocessable()->assertJsonPath('errors.rfc.0', 'Captura un RFC propio; no se admiten RFC genéricos en este formulario.');
    expect(EmpresaConfiguracion::count())->toBe(0)->and(Activity::where('event', 'empresa.updated')->count())->toBe(0);
})->with(Rfc::genericos());
