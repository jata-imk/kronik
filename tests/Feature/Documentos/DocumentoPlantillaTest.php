<?php

use App\Contracts\DocumentoPdfRenderer;
use App\Enums\DocumentoGeneradoEstado;
use App\Enums\DocumentoPlantillaVersionEstado;
use App\Jobs\GenerarDocumentoPdf;
use App\Models\Cliente;
use App\Models\DocumentoPlantilla;
use App\Models\DocumentoPlantillaVersion;
use App\Models\EmpresaConfiguracion;
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
    EmpresaConfiguracion::create(['singleton_key' => 'default', 'razon_social' => 'Financiera de prueba', 'zona_horaria' => 'America/Mexico_City']);
    $client = Cliente::factory()->create(['primer_nombre' => 'María', 'apellido_paterno' => 'Prueba']);
    $template = app(DocumentoPlantillaVersionService::class)->create(plantillaDocumentoPayload(), $user->id);
    $version = app(DocumentoPlantillaVersionService::class)->activate($template->versiones()->first());
    $key = (string) str()->uuid();

    $this->actingAs($user)->post(route('documentos-generados.store', $client), ['version_id' => $version->id, 'idempotency_key' => $key])->assertRedirect()->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('documentos-generados.store', $client), ['version_id' => $version->id, 'idempotency_key' => $key])->assertRedirect()->assertSessionHasNoErrors();
    expect($client->documentosGenerados()->count())->toBe(1);
    Queue::assertPushed(GenerarDocumentoPdf::class);

    $document = $client->documentosGenerados()->firstOrFail();
    app()->instance(DocumentoPdfRenderer::class, new class implements DocumentoPdfRenderer
    {
        public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null): string
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
    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');

    $unauthorized = User::factory()->withPersonalTeam()->create();
    $this->actingAs($unauthorized)->get(route('documentos-generados.view', $document))->assertForbidden();
    $this->actingAs($unauthorized)->get(route('documentos-generados.download', $document))->assertForbidden();
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
