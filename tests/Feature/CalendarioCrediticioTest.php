<?php

use App\Enums\ActivityEvent;
use App\Models\Activity;
use App\Models\CalendarioCrediticioVersion;
use App\Models\Credito;
use App\Services\Credito\CalendarioCrediticioService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->withoutVite();
    $this->actor = actingAsSuperAdmin();
    $this->actingAs($this->actor);
    $this->plantilla = config('calendario-crediticio.cnbv_2026');
});

test('un borrador versionado solo se publica por Super Admin y el reintento conserva una sola auditoría', function () {
    $this->post(route('productos-crediticios.calendario.store'), ['configuracion' => $this->plantilla])->assertRedirect();
    $version = CalendarioCrediticioVersion::sole();
    $ruta = route('productos-crediticios.calendario.publicar', $version);
    $this->put(route('productos-crediticios.calendario.update', $version), ['revision' => 1, 'configuracion' => array_reverse($this->plantilla, true)])
        ->assertRedirect();
    expect(Activity::where('event', ActivityEvent::CreditCalendarDraftSaved->value)->count())->toBe(1);
    $this->postJson(route('productos-crediticios.calendario.previa', $version), [
        'revision' => 2, 'inicio' => '2026-11-07', 'periodicidad' => 'semanal', 'metodo' => 'capital_fijo', 'plazo' => 1, 'gracia' => 3,
    ])->assertUnprocessable()->assertJsonValidationErrors('revision');
    $usuario = \App\Models\User::factory()->withPersonalTeam()->create();
    $equipo = $usuario->ownedTeams()->sole();
    $usuario->forceFill(['current_team_id' => $equipo->id])->save();
    grantTeamPermissions($usuario, $equipo, [CalendarioCrediticioService::CONSULTAR, CalendarioCrediticioService::EDITAR]);
    $this->actingAs($usuario)->postJson($ruta, ['revision' => 1])->assertForbidden();

    $this->actingAs($this->actor)->post($ruta, ['revision' => 1])->assertRedirect();
    $this->putJson(route('productos-crediticios.calendario.update', $version->id + 1), [])->assertNotFound();
    $this->postJson(route('productos-crediticios.calendario.publicar', $version->id + 1), [])->assertNotFound();
    $this->post($ruta, ['revision' => 1])->assertRedirect();
    $this->putJson(route('productos-crediticios.calendario.update', $version), ['revision' => 1, 'configuracion' => $this->plantilla])
        ->assertUnprocessable()->assertJsonValidationErrors('revision');

    $evento = Activity::where('event', ActivityEvent::CreditCalendarPublished->value)->sole();
    expect($evento->causer_id)->toBe($this->actor->id)
        ->and($evento->team_id)->toBe($this->actor->current_team_id)
        ->and($evento->subject_id)->toBe($version->id)
        ->and($evento->properties->all())->not->toHaveKey('configuracion');
});

test('la previa compara fechas, interés y gracia sin escribir operaciones ni actividad', function (string $metodo, ?string $politica) {
    $version = app(CalendarioCrediticioService::class)->guardar(null, ['configuracion' => $this->plantilla], $this->actor);
    $eventos = Activity::count();
    $this->postJson(route('productos-crediticios.calendario.previa', $version), [
        'revision' => 1, 'inicio' => '2026-11-07', 'periodicidad' => 'semanal', 'metodo' => $metodo, 'plazo' => 1, 'gracia' => 3, 'politica_bullet' => $politica,
    ])->assertOk()
        ->assertJsonPath('cambios.0.fecha_original', '2026-11-14')
        ->assertJsonPath('cambios.0.fecha', '2026-11-17')
        ->assertJsonCount(3, 'cambios.0.inhabiles')
        ->assertJsonPath('original.periodos.0.interes', '84.00')
        ->assertJsonPath('ajustado.periodos.0.interes', '120.00')
        ->assertJsonPath('ajustado.periodos.0.atraso_desde', '2026-11-18')
        ->assertJsonPath('ajustado.periodos.0.mora_desde', '2026-11-21');
    expect(Credito::count())->toBe(0)->and(Activity::count())->toBe($eventos);
})->with([['capital_fijo', null], ['cuota_nivelada', null], ['bullet', 'periodicos'], ['bullet', 'al_vencimiento']]);

test('oculta los borradores reservados antes de validar la previa', function () {
    $borrador = app(CalendarioCrediticioService::class)->guardar(null, ['configuracion' => $this->plantilla], $this->actor);
    $usuario = \App\Models\User::factory()->withPersonalTeam()->create();
    $equipo = $usuario->ownedTeams()->sole();
    $usuario->forceFill(['current_team_id' => $equipo->id])->save();
    grantTeamPermissions($usuario, $equipo, [CalendarioCrediticioService::CONSULTAR]);
    $this->actingAs($usuario);

    $this->postJson(route('productos-crediticios.calendario.previa', $borrador), [])->assertNotFound();
    $this->postJson(route('productos-crediticios.calendario.previa', $borrador->id + 1), [])->assertNotFound();
    foreach ([$borrador->id, $borrador->id + 1] as $id) {
        $this->putJson(route('productos-crediticios.calendario.update', $id), [])->assertForbidden();
        $this->postJson(route('productos-crediticios.calendario.publicar', $id), [])->assertForbidden();
    }

    $destinos = app(\App\Services\MenubarDestinoService::class);
    $contexto = app(\App\Services\TeamContext::class);
    $contexto->run($usuario, $equipo, function () use ($destinos, $usuario, $borrador) {
        expect($destinos->permitido('productos-crediticios.calendario.index', [], $usuario))->toBeTrue()
            ->and($destinos->permitido('productos-crediticios.calendario.show', ['version' => $borrador->id], $usuario))->toBeFalse();
    });
});

test('expone el nombre de negocio de un feriado incompleto', function () {
    $configuracion = $this->plantilla;
    $configuracion['feriados'][0]['nombre'] = '';
    $respuesta = $this->postJson(route('productos-crediticios.calendario.store'), ['configuracion' => $configuracion])
        ->assertUnprocessable();
    expect($respuesta->json('errors')['configuracion.feriados.0.nombre'])
        ->toBe(['El campo nombre del feriado es obligatorio.']);
});

test('la publicación revierte la versión si falla la auditoría', function () {
    $version = app(CalendarioCrediticioService::class)->guardar(null, ['configuracion' => $this->plantilla], $this->actor);
    $this->mock(\App\Services\ActivityLogService::class)->shouldReceive('log')->once()->andThrow(new RuntimeException('Fallo de auditoría'));

    expect(fn () => app(CalendarioCrediticioService::class)->publicar($version, 1, $this->actor))
        ->toThrow(RuntimeException::class, 'Fallo de auditoría');
    expect($version->fresh()->publicada_en)->toBeNull()
        ->and($version->fresh()->orden_publicacion)->toBeNull();
});

test('el ajuste reporta la primera fecha fuera de cobertura y rechaza vencimientos coincidentes', function () {
    $calendario = app(CalendarioCrediticioService::class);
    $version = new CalendarioCrediticioVersion(['configuracion' => $this->plantilla]);
    expect(fn () => $calendario->ajustar($version, [CarbonImmutable::parse('2027-01-01')]))
        ->toThrow(\Illuminate\Validation\ValidationException::class, '2027-01-01');
    expect(fn () => $calendario->ajustar($version, [CarbonImmutable::parse('2026-11-14'), CarbonImmutable::parse('2026-11-17')]))
        ->toThrow(\Illuminate\Validation\ValidationException::class, 'coinciden');
});
