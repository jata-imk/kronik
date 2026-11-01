<?php

use App\Enums\ActivityEvent;
use App\Models\Activity;
use App\Models\Cliente;
use App\Models\Solicitud;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function operadorSucursal(): array
{
    $actor = User::factory()->withPersonalTeam()->create();
    $branches = Sucursal::factory()->count(2)->create();
    $team = $actor->ownedTeams()->firstOrFail();
    $actor->forceFill(['current_team_id' => $team->id, 'current_sucursal_id' => $branches[0]->id])->save();
    $actor->sucursales()->attach($branches->modelKeys());
    grantTeamPermissions($actor, $team, ['read clientes', 'read solicitudes', 'read historial-crediticio']);

    return [$actor, $branches, $team];
}

test('bandejas consultan la activa y el alcance institucional es explícito', function () {
    [$actor, $branches] = operadorSucursal();
    foreach ($branches as $branch) {
        $cliente = Cliente::factory()->create(['sucursal_id' => $branch->id]);
        Solicitud::create(['cliente_id' => $cliente->id, 'sucursal_id' => $branch->id,
            'creada_por' => $actor->id, 'responsable_id' => $actor->id,
            'clave_creacion' => (string) str()->uuid(), 'creacion_hash' => str_repeat('0', 64)]);
    }
    $this->actingAs($actor)->get(route('solicitudes.index'))->assertInertia(fn (Assert $page) => $page
        ->where('solicitudes.total', 1)->where('solicitudes.data.0.sucursal_id', $branches[0]->id));
    $this->get(route('solicitudes.index', ['scope' => 'all']))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 2));
    $this->from(route('solicitudes.index', ['scope' => 'all', 'page' => 7]))
        ->put(route('current-sucursal.update'), ['sucursal_id' => $branches[1]->id])->assertRedirect(route('solicitudes.index'));
    $this->actingAs($actor->fresh())->get(route('solicitudes.index'))->assertInertia(fn (Assert $page) => $page
        ->where('solicitudes.total', 1)->where('solicitudes.data.0.sucursal_id', $branches[1]->id));
    $event = Activity::where('event', ActivityEvent::ActiveBranchChanged->value)->sole();
    expect($event->sucursal_id)->toBe($branches[0]->id)->and($event->causer_id)->toBe($actor->id);
    $this->put(route('current-sucursal.update'), ['sucursal_id' => $branches[1]->id]);
    expect(Activity::where('event', ActivityEvent::ActiveBranchChanged->value)->count())->toBe(1);
});

test('API establece equipo del token y restaura contexto incluso al denegar', function () {
    [$actor, $branches, $team] = operadorSucursal();
    $other = Team::factory()->create(['user_id' => $actor->id]);
    setPermissionsTeamId($other->id);
    Laravel\Sanctum\Sanctum::actingAs($actor);
    $this->getJson(route('api.clientes.index'))->assertOk();
    expect(getPermissionsTeamId())->toBe($other->id);
    $actor->forceFill(['current_team_id' => $other->id])->save();
    setPermissionsTeamId($team->id);
    Laravel\Sanctum\Sanctum::actingAs($actor->fresh());
    $this->getJson(route('api.clientes.index'))->assertForbidden();
    expect(getPermissionsTeamId())->toBe($team->id);
});

test('sin activa no se amplía una bandeja y los catálogos no reciben aislamiento', function () {
    [$actor] = operadorSucursal();
    $actor->forceFill(['current_sucursal_id' => null])->save();
    $this->actingAs($actor)->get(route('solicitudes.index'))->assertInertia(fn (Assert $page) => $page
        ->where('filters.requiereSucursal', true)->where('solicitudes.total', 0));
    $this->get(route('solicitudes.index', ['scope' => 'inventado']))->assertSessionHasErrors('scope');
});

test('clientes busca fuera de la primera página y valida filtros antes de consultar', function () {
    [$actor, $branches] = operadorSucursal();
    Cliente::factory()->count(27)->create(['sucursal_id' => $branches[0]->id]);
    $target = Cliente::factory()->create(['sucursal_id' => $branches[0]->id, 'primer_nombre' => 'ObjetivoRemoto', 'apellido_paterno' => 'ApellidoRemoto']);
    $this->actingAs($actor)->get(route('clientes.index', ['buscar' => 'ObjetivoRemoto ApellidoRemoto']))
        ->assertInertia(fn (Assert $page) => $page->where('clientes.total', 1)->where('clientes.data.0.id', $target->id));
    $this->get(route('clientes.index', ['por_pagina' => 10, 'page' => 3]))
        ->assertInertia(fn (Assert $page) => $page->where('clientes.total', 28)->has('clientes.data', 8));
    $this->get(route('clientes.index', ['columnas' => 'null']))->assertSessionHasErrors('columnas');
    $this->get(route('clientes.index', ['columnas' => '{"secreto":"x"}']))->assertSessionHasErrors('columnas');
    foreach (['pais_nacimiento.nombre_es', 'datos_fiscales.rfc', 'sucursal.nombre'] as $field) {
        $this->get(route('clientes.index', ['orden' => $field]))->assertOk();
    }
});

test('historial SIC filtra la sucursal almacenada y audita evidencia remota sin inferir el traslado', function () {
    [$actor, $branches, $team] = operadorSucursal();
    $this->seed(\Database\Seeders\SicsSeeder::class);
    $cliente = Cliente::factory()->create(['sucursal_id' => $branches[1]->id]);
    $queries = collect();
    foreach ([$branches[0]->id, $branches[1]->id, null] as $branchId) {
        $queries->push(\App\Models\SicQuery::create([
            'cliente_id' => $cliente->id, 'sucursal_id' => $branchId,
            'sic_id' => \App\Models\Sic::firstOrFail()->id,
            'sic_api_id' => \App\Models\SicApi::firstOrFail()->id,
            'fecha_consulta' => now(), 'status' => 'success',
        ]));
    }
    $this->actingAs($actor)->get(route('clientes.historial-crediticio.index'))
        ->assertInertia(fn (Assert $page) => $page->where('consultas.total', 1)
            ->where('consultas.data.0.id', $queries[0]->id));
    $this->get(route('clientes.historial-crediticio.index', ['scope' => 'all']))
        ->assertInertia(fn (Assert $page) => $page->where('consultas.total', 3));
    $this->get(route('clientes.historial-crediticio.show', $cliente))
        ->assertInertia(fn (Assert $page) => $page->where('consultas.total', 3));
    $this->get(route('consultas-sic.show', $queries[1]))->assertOk();
    $event = Activity::where('event', ActivityEvent::SicEvidenceViewed->value)->sole();
    expect($event->sucursal_id)->toBe($branches[1]->id)->and($event->team_id)->toBe($team->id)
        ->and($event->causer_id)->toBe($actor->id);
});

test('super admin conserva lectura remota pero las capacidades de originación exigen la sucursal responsable', function () {
    [$actor, $branches] = operadorSucursal();
    $actor->forceFill(['is_super_admin' => true, 'current_sucursal_id' => $branches[1]->id])->save();
    $cliente = Cliente::factory()->create(['sucursal_id' => $branches[0]->id]);
    $solicitud = Solicitud::create(['cliente_id' => $cliente->id, 'sucursal_id' => $branches[0]->id,
        'creada_por' => $actor->id, 'responsable_id' => $actor->id,
        'clave_creacion' => (string) str()->uuid(), 'creacion_hash' => str_repeat('0', 64)]);
    $this->actingAs($actor)->get(route('solicitudes.show', $solicitud))
        ->assertInertia(fn (Assert $page) => $page->where('can.update', false)->where('can.assign', false));
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('can.preparar', false)->where('can.recibirFirma', false)->where('can.revisarFirma', false));
    $this->get(route('solicitudes.edit', $solicitud))->assertForbidden();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'No debe guardarse'])
        ->assertSessionHasErrors('sucursal');
    expect($solicitud->fresh()->destino)->toBeNull();
});

test('consulta mínima de contexto no depende de permisos del módulo y no se cachea', function () {
    [$actor, $branches, $team] = operadorSucursal();
    $this->actingAs($actor)->getJson(route('contexto-operativo.show'))->assertOk()
        ->assertExactJson(['team_id' => $team->id, 'sucursal_id' => $branches[0]->id])
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $actor->forceFill(['current_team_id' => null, 'current_sucursal_id' => null])->save();
    $this->actingAs($actor->fresh())->getJson(route('contexto-operativo.show'))->assertOk()
        ->assertExactJson(['team_id' => null, 'sucursal_id' => null]);
});

test('auditoría administrativa de expediente conserva la sucursal del cliente remoto', function () {
    [$actor, $branches, $team] = operadorSucursal();
    $actor->forceFill(['is_super_admin' => true])->save();
    $cliente = Cliente::factory()->create(['sucursal_id' => $branches[1]->id]);
    $this->actingAs($actor)->patch(route('clientes.expediente.perfil.update', $cliente), [
        'ocupacion' => 'Ingeniera', 'actividad_economica' => 'Servicios profesionales',
        'ingresos_mensuales' => 40000, 'egresos_mensuales' => 20000, 'origen_recursos' => 'Trabajo independiente',
    ])->assertRedirect()->assertSessionHasNoErrors();
    $event = Activity::where('event', ActivityEvent::ClientKycUpdated->value)->sole();
    expect($event->sucursal_id)->toBe($branches[1]->id)->and($event->team_id)->toBe($team->id);
    $this->patch(route('clientes.sucursal.transfer', $cliente), ['sucursal_id' => $branches[0]->id])->assertRedirect()->assertSessionHasNoErrors();
    $transfer = Activity::where('event', ActivityEvent::ClientTransferred->value)->sole();
    expect($transfer->sucursal_id)->toBe($branches[1]->id)->and($cliente->fresh()->sucursal_id)->toBe($branches[0]->id);
});

test('selector institucional del expediente busca fuera de sus primeras opciones y autoriza acceso directo', function () {
    [$actor, $branches] = operadorSucursal();
    $cliente = Cliente::factory()->create(['sucursal_id' => $branches[0]->id]);
    Cliente::factory()->count(24)->create(['primer_nombre' => 'Anterior']);
    $target = Cliente::factory()->create(['primer_nombre' => 'ObjetivoVinculo', 'sucursal_id' => $branches[1]->id]);
    $this->actingAs($actor)->get(route('clientes.expediente.show', $cliente))->assertInertia(fn (Assert $page) => $page->has('clientesDisponibles', 20));
    $this->getJson(route('clientes.expediente.clientes', [$cliente, 'buscar' => 'ObjetivoVinculo']))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $target->id);
    $this->actingAs(User::factory()->create())->getJson(route('clientes.expediente.clientes', $cliente))->assertForbidden();
});

test('responsables usa permisos del equipo de la acción y restaura Spatie sin usar preferencias del candidato', function () {
    [$actor, $branches, $team] = operadorSucursal();
    grantTeamPermissions($actor, $team, ['assign solicitudes']);
    $other = Team::factory()->create(['user_id' => $actor->id]);
    $candidate = User::factory()->create(['name' => 'Responsable remoto', 'current_team_id' => $other->id]);
    $team->users()->attach($candidate);
    $candidate->sucursales()->attach($branches[0]);
    grantTeamPermissions($candidate, $team, ['read clientes', 'read solicitudes']);
    $cliente = Cliente::factory()->create(['sucursal_id' => $branches[0]->id]);
    $solicitud = Solicitud::create(['cliente_id' => $cliente->id, 'sucursal_id' => $branches[0]->id,
        'creada_por' => $actor->id, 'responsable_id' => $actor->id,
        'clave_creacion' => (string) str()->uuid(), 'creacion_hash' => str_repeat('0', 64)]);
    setPermissionsTeamId($other->id);
    $this->actingAs($actor)->getJson(route('solicitudes.responsables.index', [$solicitud, 'buscar' => 'Responsable remoto']))
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $candidate->id);
    expect(getPermissionsTeamId())->toBe($other->id)->and($candidate->fresh()->current_team_id)->toBe($other->id);
    $actor->forceFill(['current_sucursal_id' => $branches[1]->id, 'is_super_admin' => true])->save();
    $this->actingAs($actor->fresh())->getJson(route('solicitudes.responsables.index', $solicitud))->assertForbidden();
});

test('una preferencia histórica de sucursal inactiva no permite capturar ni modificar clientes normales', function () {
    [$actor, $branches, $team] = operadorSucursal();
    activarEmpresaPrueba();
    grantTeamPermissions($actor, $team, ['create clientes', 'update clientes', 'delete clientes']);
    $cliente = Cliente::factory()->create(['sucursal_id' => $branches[0]->id]);
    $branches[0]->update(['activa' => false]);
    $this->actingAs($actor->fresh())->get(route('clientes.edit', $cliente))->assertForbidden();
    $this->patch(route('clientes.expediente.perfil.update', $cliente), ['ocupacion' => 'No permitido'])->assertForbidden();
    $this->delete(route('clientes.destroy', $cliente))->assertForbidden();
    $actor->forceFill(['is_super_admin' => true])->save();
    $this->actingAs($actor->fresh());
    expect(fn () => app(\App\Services\ClienteService::class)->store([]))->toThrow(\Illuminate\Validation\ValidationException::class, 'Selecciona una sucursal activa');
    expect(Activity::where('event', ActivityEvent::ClientCreated->value)->count())->toBe(0);
});
