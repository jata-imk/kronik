<?php

use App\Models\OriginacionPolitica;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\OriginacionPoliticaService;
use App\Services\ProductoVersionService;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('política tiene número independiente y lectores consultan historia exacta fuera de página', function () {
    $actor = actingAsSuperAdmin();
    $version = solicitudProducto($actor->id);
    $service = app(OriginacionPoliticaService::class);
    $first = null;
    for ($i = 0; $i < 13; $i++) {
        $p = $service->crear($version, [...politicaSolicitudDatos(), 'version_anterior' => $i, 'vigencia_dias' => $i + 1], $actor);
        $first ??= $p;
    }
    $reader = User::factory()->withPersonalTeam()->create();
    $reader->forceFill(['current_team_id' => $reader->ownedTeams()->first()->id])->save();
    grantTeamPermissions($reader, $reader->currentTeam, ['read productos-crediticios']);
    $count = \Spatie\Activitylog\Models\Activity::count();
    $this->actingAs($reader)->get(route('originacion-politicas.show', ['version' => $version->id, 'politica_id' => $first->id, 'page' => 2, 'modo' => 'nueva']))
        ->assertInertia(fn (Assert $p) => $p->where('politica.numero', 13)->where('politicaSeleccionada.id', $first->id)
            ->where('politicaSeleccionada.condiciones.vigencia_dias', 1)->where('version.numero', 1)
            ->where('puedeCrear', false)->where('modoPolitica', 'consulta')->where('historial.current_page', 2));
    $this->get(route('productos-crediticios.index', ['producto_id' => $version->producto_crediticio_id]))
        ->assertInertia(fn (Assert $p) => $p->where('productoSeleccionado.ultimaVersion.originacion.numero', 13));
    expect(\Spatie\Activitylog\Models\Activity::count())->toBe($count);
    $this->postJson(route('originacion-politicas.store', $version), [...politicaSolicitudDatos(), 'version_anterior' => 13])->assertForbidden();
    $other = solicitudProducto($actor->id);
    $this->get(route('originacion-politicas.show', ['version' => $other->id, 'politica_id' => $first->id]))->assertNotFound();
});

test('retirada conserva consulta y rechaza políticas nuevas incluso con instancia antigua y super admin', function () {
    $actor = actingAsSuperAdmin();
    $version = solicitudProducto($actor->id);
    $service = app(OriginacionPoliticaService::class);
    $service->crear($version, politicaSolicitudDatos(), $actor);
    app(ProductoVersionService::class)->retirar($version->fresh());
    $this->actingAs($actor)->get(route('originacion-politicas.show', $version))->assertInertia(fn (Assert $p) => $p->where('puedeCrear', false));
    $before = \Spatie\Activitylog\Models\Activity::count();
    expect(fn () => $service->crear($version, [...politicaSolicitudDatos(), 'version_anterior' => 1], $actor))->toThrow(ValidationException::class);
    $this->postJson(route('originacion-politicas.store', $version), [...politicaSolicitudDatos(), 'version_anterior' => 1])->assertUnprocessable()->assertJsonValidationErrors('politica');
    expect(OriginacionPolitica::count())->toBe(1)->and(\Spatie\Activitylog\Models\Activity::count())->toBe($before);
});

test('sin política mantiene captura y envío y orienta aprobación sin conceder acceso al catálogo', function () {
    activarEmpresaPrueba();
    $actor = actingAsSuperAdmin();
    $version = solicitudProducto($actor->id);
    $cliente = \App\Models\Cliente::factory()->create(['sucursal_id' => $actor->current_sucursal_id]);
    $this->actingAs($actor)->post(route('solicitudes.store'), solicitudDatos($cliente, $version))->assertSessionHasNoErrors();
    $solicitud = Solicitud::sole();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => $solicitud->lock_version])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado->value)->toBe('en_revision');
    $result = app(\App\Services\SolicitudRequisitosService::class)->evaluar($solicitud->fresh(), $actor);
    $pending = collect($result['requisitos'])->firstWhere('clave', 'politica');
    expect($pending['cumplido'])->toBeFalse()->and($pending['accion']['ruta'])->toBe('originacion-politicas.show')
        ->and($pending['ubicacion'])->toContain('Política de originación');
    $other = User::factory()->withPersonalTeam()->create();
    $summary = app(OriginacionPoliticaService::class)->resumen($version, $other);
    expect($summary['accion'])->toBeNull()->and($summary['siguiente'])->toContain('administrador');
});
