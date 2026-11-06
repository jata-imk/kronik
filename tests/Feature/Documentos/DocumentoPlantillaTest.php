<?php

use App\Contracts\DocumentoPdfRenderer;
use App\Enums\DocumentoGeneradoEstado;
use App\Enums\DocumentoPlantillaVersionEstado;
use App\Jobs\GenerarDocumentoPdf;
use App\Models\Cliente;
use App\Models\DocumentoPlantilla;
use App\Models\DocumentoPlantillaVersion;
use App\Models\User;
use App\Services\Documentos\DocumentoPlantillaVersionService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function plantillaDocumentoPayload(array $overrides = []): array
{
    return [
        'clave' => 'consentimiento-sic-v1',
        'nombre' => 'Consentimiento SIC general',
        'tipo' => 'consentimiento_sic',
        'descripcion' => 'Texto aprobado por la financiera',
        'encabezado_html' => '<p>{{empresa.razon_social}}</p>',
        'contenido_html' => '<h1>Consentimiento</h1><p>Yo, {{cliente.nombre_completo}}, autorizo la consulta.</p>',
        'pie_html' => '<p>{{documento.fecha_generacion}}</p>',
        'resumen_cambios' => 'Versión inicial de prueba',
        ...$overrides,
    ];
}

test('super admin crea y consulta plantillas globales', function () {
    $user = actingAsSuperAdmin();

    $this->actingAs($user)->post(route('plantillas-documentos.store'), plantillaDocumentoPayload())->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('documento_plantillas', ['clave' => 'consentimiento-sic-v1', 'tipo' => 'consentimiento_sic']);
    $this->assertDatabaseHas('documento_plantilla_versiones', ['numero' => 1, 'estado' => 'borrador']);
    $this->actingAs($user)->get(route('plantillas-documentos.index'))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('Documentos/Plantillas/Index')->has('plantillas', 1)->has('variables.consentimiento_sic'),
    );
});

test('activar congela contenido y duplicar conserva el historial', function () {
    $user = actingAsSuperAdmin();
    $template = app(DocumentoPlantillaVersionService::class)->create(plantillaDocumentoPayload(), $user->id);
    $version = $template->versiones()->firstOrFail();

    $this->actingAs($user)->post(route('plantillas-documentos.activar', $version))->assertRedirect()->assertSessionHasNoErrors();
    expect($version->fresh()->estado)->toBe(DocumentoPlantillaVersionEstado::Activa)
        ->and($version->fresh()->contenido_hash)->toHaveLength(64);
    expect(fn () => $version->fresh()->update(['contenido_html' => '<p>Cambio destructivo</p>']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'no puede modificarse');

    $this->actingAs($user)->post(route('plantillas-documentos.versionar', [$template, $version]))->assertRedirect();
    expect($template->versiones()->count())->toBe(2)
        ->and($template->versiones()->latest('numero')->first()->estado)->toBe(DocumentoPlantillaVersionEstado::Borrador);
});

test('ciclo de plantilla audita cada acción y conserva cambios efectivos sin contenido', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $service = app(DocumentoPlantillaVersionService::class);
    $template = $service->create(plantillaDocumentoPayload(), $actor->id);
    $first = $template->versiones()->sole();
    $data = plantillaDocumentoPayload(['nombre' => 'NOMBRE-RESERVADO', 'contenido_html' => '<p>CONTENIDO-RESERVADO</p>']);
    $service->update($template, $first, $data);
    $this->travel(1)->minutes();
    $service->update($template, $first, $data);
    expect(\App\Models\Activity::where('event', 'documento_plantilla.updated')->count())->toBe(1);
    $service->activate($first);
    $second = $service->duplicate($template, $first, $actor->id);
    $service->activate($second);
    $service->retire($second);
    assertDomainAudit(['documento_plantilla.created', 'documento_plantilla.updated', 'documento_plantilla.versioned',
        'documento_plantilla.activated', 'documento_plantilla.retired'], $actor, $actor->current_sucursal_id,
        ['NOMBRE-RESERVADO', 'CONTENIDO-RESERVADO', '{{cliente.nombre_completo}}']);
    expect(\App\Models\Activity::where('event', 'documento_plantilla.retired')->count())->toBe(2);
});

test('auditoría de plantilla revierte activación y retiro automático juntos', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $service = app(DocumentoPlantillaVersionService::class);
    $template = $service->create(plantillaDocumentoPayload(), $actor->id);
    $first = $service->activate($template->versiones()->sole());
    $second = $service->duplicate($template, $first, $actor->id);
    $before = \App\Models\Activity::count();
    $this->mock(\App\Services\ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('Auditoría no disponible'));
    expect(fn () => $service->activate($second))->toThrow(RuntimeException::class)
        ->and($first->fresh()->estado)->toBe(DocumentoPlantillaVersionEstado::Activa)
        ->and($second->fresh()->estado)->toBe(DocumentoPlantillaVersionEstado::Borrador)
        ->and(\App\Models\Activity::count())->toBe($before);
});

test('usuario sin permiso no administra plantillas y la validación es legible', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user)->get(route('plantillas-documentos.index'))->assertForbidden();

    $admin = actingAsSuperAdmin();
    $response = $this->actingAs($admin)->post(route('plantillas-documentos.store'), plantillaDocumentoPayload([
        'clave' => 'CLAVE CON ESPACIOS', 'nombre' => '', 'contenido_html' => '{{propiedad.interna}}',
    ]));
    $response->assertSessionHasErrors(['clave', 'nombre']);
    expect(collect(session('errors')->all())->implode(' '))->not->toContain('validation.')
        ->and(session('errors')->first('clave'))->toContain('minúsculas');
});

test('generación es idempotente trazable y produce un PDF privado', function () {
    Storage::fake('local');
    Queue::fake();
    $user = actingAsSuperAdmin();
    activarEmpresaPrueba();
    $client = Cliente::factory()->create(['primer_nombre' => 'María', 'apellido_paterno' => 'Prueba']);
    $template = app(DocumentoPlantillaVersionService::class)->create(plantillaDocumentoPayload(), $user->id);
    $version = app(DocumentoPlantillaVersionService::class)->activate($template->versiones()->first());
    $key = (string) str()->uuid();

    $this->actingAs($user)->post(route('documentos-generados.store', $client), ['version_id' => $version->id, 'idempotency_key' => $key])->assertRedirect()->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('documentos-generados.store', $client), ['version_id' => $version->id, 'idempotency_key' => $key])->assertRedirect()->assertSessionHasNoErrors();
    expect($client->documentosGenerados()->count())->toBe(1);
    Queue::assertPushed(GenerarDocumentoPdf::class);

    $document = $client->documentosGenerados()->firstOrFail();
    $this->actingAs($user)
        ->getJson(route('documentos-generados.status', $document))
        ->assertOk()
        ->assertJsonPath('estado', DocumentoGeneradoEstado::Pendiente->value);
    app()->instance(DocumentoPdfRenderer::class, new class implements DocumentoPdfRenderer
    {
        public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null, array $options = []): string
        {
            expect($bodyHtml)->toContain('María Prueba')->not->toContain('{{');

            return "%PDF-1.4\n% deterministic test\n%%EOF";
        }
    });
    app()->call([new GenerarDocumentoPdf($document->id), 'handle']);
    $document->refresh();

    expect($document->estado)->toBe(DocumentoGeneradoEstado::Generado)
        ->and($document->archivo_hash)->toHaveLength(64)
        ->and($document->version->id)->toBe($version->id);
    Storage::disk('local')->assertExists($document->path);
    $response = $this->actingAs($user)->get(route('documentos-generados.view', $document))->assertOk();
    $this->get(route('documentos-generados.download', $document))->assertOk();
    assertDomainAudit(['documento.viewed', 'documento.downloaded'], $user, $document->sucursal_id, ['María Prueba', 'deterministic test']);
    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');
    $this->actingAs($user)
        ->getJson(route('documentos-generados.status', $document))
        ->assertOk()
        ->assertJsonPath('estado', DocumentoGeneradoEstado::Generado->value);

    $unauthorized = User::factory()->withPersonalTeam()->create();
    $this->actingAs($unauthorized)->getJson(route('documentos-generados.status', $document))->assertForbidden();
    $this->actingAs($unauthorized)->get(route('documentos-generados.view', $document))->assertForbidden();
    $this->actingAs($unauthorized)->get(route('documentos-generados.download', $document))->assertForbidden();
});

test('regeneración bloquea trabajos activos y exige confirmar una nueva copia', function () {
    Queue::fake();
    $user = actingAsSuperAdmin();
    activarEmpresaPrueba();
    $client = Cliente::factory()->create();
    $template = app(DocumentoPlantillaVersionService::class)->create(plantillaDocumentoPayload(), $user->id);
    $version = app(DocumentoPlantillaVersionService::class)->activate($template->versiones()->firstOrFail());

    $this->actingAs($user)->post(route('documentos-generados.store', $client), [
        'version_id' => $version->id,
        'idempotency_key' => (string) str()->uuid(),
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post(route('documentos-generados.store', $client), [
        'version_id' => $version->id,
        'idempotency_key' => (string) str()->uuid(),
    ])->assertSessionHasErrors([
        'version_id' => 'Ya existe una generación en curso para esta plantilla y contexto. Espere a que termine antes de solicitar otra.',
    ]);

    $existing = $client->documentosGenerados()->firstOrFail();
    $existing->update([
        'estado' => DocumentoGeneradoEstado::Generado,
        'disk' => 'local',
        'path' => "documentos-generados/{$existing->id}.pdf",
        'nombre_archivo' => 'consentimiento.pdf',
        'mime_type' => 'application/pdf',
        'generado_en' => now(),
    ]);

    $this->actingAs($user)
        ->getJson(route('documentos-generados.existing', $client).'?version_id='.$version->id)
        ->assertOk()
        ->assertJsonPath('documento.id', $existing->id)
        ->assertJsonPath('documento.estado', 'generado')
        ->assertJsonPath('documento.bloquea', false);

    $this->actingAs($user)->post(route('documentos-generados.store', $client), [
        'version_id' => $version->id,
        'idempotency_key' => (string) str()->uuid(),
    ])->assertSessionHasErrors([
        'confirm_duplicate' => 'Ya existe un PDF generado para esta plantilla y contexto. Confirme que desea crear otra copia trazable.',
    ]);

    $this->actingAs($user)->post(route('documentos-generados.store', $client), [
        'version_id' => $version->id,
        'idempotency_key' => (string) str()->uuid(),
        'confirm_duplicate' => true,
    ])->assertSessionHasNoErrors();

    expect($client->documentosGenerados()->count())->toBe(2);
});

test('contratos no se generan antes de originación', function () {
    Queue::fake();
    $user = actingAsSuperAdmin();
    $client = Cliente::factory()->create();
    $template = app(DocumentoPlantillaVersionService::class)->create(plantillaDocumentoPayload([
        'clave' => 'contrato-base', 'tipo' => 'contrato', 'contenido_html' => '<p>{{cliente.nombre_completo}}</p>',
    ]), $user->id);
    $version = app(DocumentoPlantillaVersionService::class)->activate($template->versiones()->first());

    $this->actingAs($user)->post(route('documentos-generados.store', $client), [
        'version_id' => $version->id, 'idempotency_key' => (string) str()->uuid(),
    ])->assertSessionHasErrors(['version_id']);
    Queue::assertNothingPushed();
});

test('visor rechaza MIME activo y recorridos de ruta', function () {
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    $client = Cliente::factory()->create();
    $template = DocumentoPlantilla::create(['clave' => 'segura', 'nombre' => 'Segura', 'tipo' => 'consentimiento_sic', 'creada_por' => $user->id]);
    $version = DocumentoPlantillaVersion::create(['documento_plantilla_id' => $template->id, 'numero' => 1, 'estado' => 'activa', 'contenido_html' => '<p>Seguro</p>', 'contenido_hash' => str_repeat('a', 64)]);
    Storage::disk('local')->put('documentos-generados/activo.pdf', '<html><script>alert(1)</script></html>');
    $document = $client->documentosGenerados()->create(['documento_plantilla_version_id' => $version->id, 'documentable_type' => 'clientes', 'documentable_id' => $client->id, 'estado' => 'generado', 'idempotency_key' => str()->uuid(), 'datos_utilizados' => [], 'disk' => 'local', 'path' => 'documentos-generados/activo.pdf', 'nombre_archivo' => 'activo.pdf', 'mime_type' => 'application/pdf', 'solicitado_en' => now()]);

    $this->actingAs($user)->get(route('documentos-generados.view', $document))->assertStatus(415);
    $document->update(['path' => '../secreto.pdf']);
    $this->actingAs($user)->get(route('documentos-generados.view', $document))->assertNotFound();
});

test('solicitud PDF y auditoría se revierten juntas sin despachar trabajo', function () {
    Queue::fake();
    $user = actingAsSuperAdmin();
    activarEmpresaPrueba();
    $client = Cliente::factory()->create();
    $service = app(DocumentoPlantillaVersionService::class);
    $template = $service->create(plantillaDocumentoPayload(), $user->id);
    $version = $service->activate($template->versiones()->sole());
    $before = \App\Models\Activity::count();
    $this->mock(\App\Services\ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('Auditoría no disponible'));
    expect(fn () => app(\App\Services\Documentos\DocumentoGeneracionService::class)->request($client, $version, null, (string) str()->uuid(), $user))
        ->toThrow(RuntimeException::class)->and($client->documentosGenerados()->count())->toBe(0)
        ->and(\App\Models\Activity::count())->toBe($before);
    Queue::assertNothingPushed();
});

test('fallo terminal PDF es atómico y no se duplica ni sustituye un resultado generado', function () {
    Queue::fake();
    $user = actingAsSuperAdmin();
    activarEmpresaPrueba();
    $client = Cliente::factory()->create();
    $service = app(DocumentoPlantillaVersionService::class);
    $template = $service->create(plantillaDocumentoPayload(), $user->id);
    $version = $service->activate($template->versiones()->sole());
    $document = app(\App\Services\Documentos\DocumentoGeneracionService::class)->request($client, $version, null, (string) str()->uuid(), $user);
    $job = new GenerarDocumentoPdf($document->id);
    $this->mock(\App\Services\ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('Auditoría no disponible'));
    expect(fn () => $job->failed(new RuntimeException('error con secreto')))->toThrow(RuntimeException::class)
        ->and($document->fresh()->estado)->toBe(DocumentoGeneradoEstado::Pendiente);
    app()->forgetInstance(\App\Services\ActivityLogService::class);
    $job->failed(new RuntimeException('error con secreto'));
    $job->failed(new RuntimeException('error con secreto'));
    expect(\App\Models\Activity::where('event', \App\Enums\ActivityEvent::DocumentGenerationFailed->value)->count())->toBe(1)
        ->and($document->fresh()->estado)->toBe(DocumentoGeneradoEstado::Fallido)
        ->and(\App\Models\Activity::all()->toJson())->not->toContain('error con secreto');
    $document->update(['estado' => DocumentoGeneradoEstado::Generado, 'archivo_hash' => str_repeat('a', 64)]);
    $job->failed(new RuntimeException('tardío'));
    expect($document->fresh()->estado)->toBe(DocumentoGeneradoEstado::Generado);
});

test('resultado PDF solo se confirma con auditoría y una reentrega no duplica éxito', function () {
    Queue::fake();
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    activarEmpresaPrueba();
    $client = Cliente::factory()->create();
    $templates = app(DocumentoPlantillaVersionService::class);
    $template = $templates->create(plantillaDocumentoPayload(), $user->id);
    $version = $templates->activate($template->versiones()->sole());
    $document = app(\App\Services\Documentos\DocumentoGeneracionService::class)->request($client, $version, null, (string) str()->uuid(), $user);
    app()->instance(DocumentoPdfRenderer::class, new class implements DocumentoPdfRenderer
    {
        public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null, array $options = []): string
        {
            return "%PDF-1.4\n% resultado sintético\n%%EOF";
        }
    });
    $this->mock(\App\Services\ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('Auditoría no disponible'));
    $job = new GenerarDocumentoPdf($document->id);
    expect(fn () => app()->call([$job, 'handle']))->toThrow(RuntimeException::class)
        ->and($document->fresh()->estado)->toBe(DocumentoGeneradoEstado::Procesando)
        ->and($document->fresh()->archivo_hash)->toBeNull()
        ->and(\App\Models\Activity::where('event', 'documento.generated')->count())->toBe(0);
    app()->forgetInstance(\App\Services\ActivityLogService::class);
    app()->call([$job, 'handle']);
    app()->call([$job, 'handle']);
    expect($document->fresh()->estado)->toBe(DocumentoGeneradoEstado::Generado)
        ->and(\App\Models\Activity::where('event', 'documento.generated')->count())->toBe(1);
});

test('fallo al regenerar PDF común perdido queda auditable y permite solicitar nueva copia', function () {
    Queue::fake();
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    activarEmpresaPrueba();
    $client = Cliente::factory()->create();
    $templates = app(DocumentoPlantillaVersionService::class);
    $template = $templates->create(plantillaDocumentoPayload(), $user->id);
    $version = $templates->activate($template->versiones()->sole());
    $generation = app(\App\Services\Documentos\DocumentoGeneracionService::class);
    $document = $generation->request($client, $version, null, (string) str()->uuid(), $user);
    $hash = str_repeat('a', 64);
    $document->update(['estado' => DocumentoGeneradoEstado::Generado, 'archivo_hash' => $hash,
        'disk' => 'local', 'path' => 'documentos/perdido.pdf']);
    $this->mock(DocumentoPdfRenderer::class)->shouldReceive('render')->once()->andThrow(new RuntimeException('Fallo sintético'));
    $job = new GenerarDocumentoPdf($document->id);
    expect(fn () => app()->call([$job, 'handle']))->toThrow(RuntimeException::class)
        ->and($document->fresh()->estado)->toBe(DocumentoGeneradoEstado::Procesando);
    $job->failed(new RuntimeException('Fallo sintético'));
    $job->failed(new RuntimeException('Fallo sintético'));
    expect($document->fresh()->estado)->toBe(DocumentoGeneradoEstado::Fallido)
        ->and($document->fresh()->archivo_hash)->toBe($hash)
        ->and(\App\Models\Activity::where('event', 'documento.generacion.failed')->count())->toBe(1);
    $new = $generation->request($client, $version, null, (string) str()->uuid(), $user);
    expect($new->id)->not->toBe($document->id)->and($new->estado)->toBe(DocumentoGeneradoEstado::Pendiente);
});

test('PDF conserva contexto original y no reconstruye sucursal histórica desconocida', function (bool $unknown) {
    Storage::fake('local');
    Queue::fake();
    $user = actingAsSuperAdmin();
    activarEmpresaPrueba();
    $branch = \App\Models\Sucursal::factory()->create();
    $client = Cliente::factory()->create(['sucursal_id' => $branch->id]);
    $service = app(DocumentoPlantillaVersionService::class);
    $template = $service->create(plantillaDocumentoPayload(), $user->id);
    $version = $service->activate($template->versiones()->first());
    $this->actingAs($user)->post(route('documentos-generados.store', $client), [
        'version_id' => $version->id, 'idempotency_key' => (string) str()->uuid(),
    ])->assertSessionHasNoErrors();
    $document = $client->documentosGenerados()->sole();
    $requestEvent = \App\Models\Activity::where('event', \App\Enums\ActivityEvent::DocumentGenerationRequested->value)->sole();
    expect($requestEvent->sucursal_id)->toBe($branch->id);
    if ($unknown) {
        $document->update(['sucursal_id' => null]);
    }
    $expectedBranch = $document->sucursal_id;
    $team = \App\Models\Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id, 'current_sucursal_id' => null])->save();
    $client->update(['sucursal_id' => $user->fresh()->current_sucursal_id]);
    app()->instance(DocumentoPdfRenderer::class, new class implements DocumentoPdfRenderer
    {
        public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null, array $options = []): string
        {
            return "%PDF-1.4\n% contexto sintético\n%%EOF";
        }
    });
    app()->call([new GenerarDocumentoPdf($document->id), 'handle']);
    app()->call([new GenerarDocumentoPdf($document->id), 'handle']);
    $event = \App\Models\Activity::where('event', \App\Enums\ActivityEvent::DocumentGenerated->value)->sole();
    expect($document->fresh()->estado)->toBe(DocumentoGeneradoEstado::Generado)
        ->and($event->team_id)->toBe($document->team_id)->and($event->sucursal_id)->toBe($expectedBranch)
        ->and($event->causer_id)->toBeNull()->and($document->creado_por)->toBe($user->id);
})->with([false, true]);
