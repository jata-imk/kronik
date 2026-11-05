<?php

use App\Models\User;
use App\Services\Documentos\DocumentoPlantillaVersionService;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function consultaPlantillaDatos(): array
{
    return ['clave' => 'consulta-plantilla', 'nombre' => 'Consulta sintética', 'tipo' => 'consentimiento_sic',
        'encabezado_html' => '<p>Encabezado histórico</p>', 'contenido_html' => '<p>Texto {{cliente.nombre_completo}}</p>',
        'pie_html' => '<p>Pie histórico</p>', 'presentacion' => ['marca_agua' => 'HISTORIA']];
}

test('instancia antigua de borrador no modifica contenido activo ni metadatos de la plantilla', function () {
    $actor = actingAsSuperAdmin();
    $service = app(DocumentoPlantillaVersionService::class);
    $template = $service->create(consultaPlantillaDatos(), $actor->id);
    $stale = $template->versiones()->sole();
    $active = $service->activate($stale->fresh());
    $hash = $active->contenido_hash;
    expect(fn () => $service->update($template, $stale, [...consultaPlantillaDatos(), 'nombre' => 'Cambio', 'contenido_html' => '<p>Destructivo</p>']))->toThrow(ValidationException::class);
    expect(fn () => $service->activate($stale))->toThrow(ValidationException::class);
    expect(fn () => $stale->update(['contenido_html' => '<p>Cambio directo</p>']))->toThrow(ValidationException::class);
    expect($active->fresh()->contenido_hash)->toBe($hash)->and($template->fresh()->nombre)->toBe('Consulta sintética');
    $service->retire($active);
    expect(fn () => $service->update($template, $stale, consultaPlantillaDatos()))->toThrow(ValidationException::class);
});

test('lector consulta la versión exacta fuera de página con contenido presentación y permiso efectivo', function () {
    $actor = actingAsSuperAdmin();
    $service = app(DocumentoPlantillaVersionService::class);
    $template = $service->create(consultaPlantillaDatos(), $actor->id);
    $first = $service->activate($template->versiones()->sole());
    for ($i = 0; $i < 27; $i++) {
        $service->duplicate($template, $first, $actor->id);
    }
    $reader = User::factory()->withPersonalTeam()->create();
    $reader->forceFill(['current_team_id' => $reader->ownedTeams()->first()->id])->save();
    grantTeamPermissions($reader, $reader->currentTeam, ['read plantillas-documentos']);
    $before = \Spatie\Activitylog\Models\Activity::count();
    $this->actingAs($reader)->get(route('plantillas-documentos.index', ['plantilla_id' => $template->id, 'version_id' => $first->id, 'modo' => 'consulta', 'versiones_por_pagina' => 10]))
        ->assertInertia(fn (Assert $p) => $p->where('plantillaSeleccionada.versionSeleccionada.id', $first->id)
            ->where('plantillaSeleccionada.versionSeleccionada.puede_editar', false)
            ->where('plantillaSeleccionada.versionSeleccionada.encabezado_html', '<p>Encabezado histórico</p>')
            ->where('plantillaSeleccionada.versionSeleccionada.pie_html', '<p>Pie histórico</p>')
            ->where('plantillaSeleccionada.versionSeleccionada.presentacion.marca_agua', 'HISTORIA')
            ->where('versionesListado.total', 28)->has('plantillaSeleccionada.versiones', 10));
    expect(\Spatie\Activitylog\Models\Activity::count())->toBe($before);
    $this->putJson(route('plantillas-documentos.update', [$template, $first]), consultaPlantillaDatos())->assertForbidden();
    $this->actingAs($actor)->putJson(route('plantillas-documentos.update', [$template, $first]), consultaPlantillaDatos())->assertUnprocessable();
});
