<?php

use App\Contracts\DocumentoPdfRenderer;
use App\Enums\DocumentoPlantillaTipo;
use App\Models\DocumentoGenerado;
use App\Models\DocumentoPlantilla;
use App\Models\DocumentoRecurso;
use App\Models\User;
use App\Services\Documentos\DocumentoPlantillaVersionService;
use App\Services\Documentos\DocumentoRecursoService;
use App\Services\Documentos\HtmlDocumentoSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->withoutVite();
});

function editorPayload(array $changes = []): array
{
    return array_replace([
        'clave' => 'editor-qa', 'nombre' => 'Plantilla QA', 'tipo' => 'consentimiento_sic',
        'contenido_html' => '<h1>Documento</h1><p>{{cliente.nombre_completo}}</p>',
        'encabezado_html' => '<p>Empresa de ejemplo</p>', 'pie_html' => '<p>Fin de documento</p>',
        'presentacion' => ['marca_agua' => 'BORRADOR'],
    ], $changes);
}

test('rechaza contenido visualmente vacío sin exigir insertar variables', function (string $html) {
    $user = actingAsSuperAdmin();
    $response = $this->actingAs($user)->post(route('plantillas-documentos.store'), editorPayload(['contenido_html' => $html]));
    $response->assertSessionHasErrors('contenido_html');
    expect(session('errors')->first('contenido_html'))->not->toContain('validation.');
    expect(DocumentoPlantilla::count())->toBe(0);
})->with(['<p><br></p>', '<p>&nbsp; </p>', '<script>oculto</script>', '<div class="document-page-break"></div>']);

test('el saneamiento conserva formato y elimina contenido activo anidado', function () {
    $html = '<section><script>alert(1)</script><p onclick="alert(2)" style="color:#123456;background-color:rgb(255, 255, 0);font-size:14pt;position:fixed;background:url(https://evil.test)">Texto</p></section><ul><li>Viñeta</li></ul><blockquote>Cita</blockquote><div class="document-page-break"></div><img src="https://evil.test/logo.png">';
    $safe = app(HtmlDocumentoSanitizer::class)->sanitize($html);
    expect($safe)->toContain('color:#123456', 'font-size:14pt', '<ul>', '<blockquote>', 'document-page-break')
        ->not->toContain('script', 'alert', 'onclick', 'position', 'url(', 'evil.test');
    expect(app(HtmlDocumentoSanitizer::class)->sanitize($safe))->toBe($safe);
});

test('biblioteca valida imágenes sirve archivos privados y protege los recursos', function () {
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    $response = $this->actingAs($user)->postJson(route('documento-recursos.store'), ['archivo' => UploadedFile::fake()->image('logo.png', 80, 40)]);
    $response->assertCreated()->assertJsonMissingPath('path')->assertJsonMissingPath('disk');
    $resource = DocumentoRecurso::findOrFail($response->json('id'));
    $activity = \Spatie\Activitylog\Models\Activity::where('event', 'documento_recurso.created')->firstOrFail();
    expect($activity->subject_id)->toBeNull()
        ->and($activity->properties['related']['id'])->toBe($resource->id)
        ->and($activity->properties->toJson())->not->toContain($resource->path, 'base64');
    $this->get(route('documento-recursos.show', $resource))->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->getJson(route('documento-recursos.index'))->assertJsonPath('data.0.id', $resource->id);
    expect(fn () => $resource->update(['nombre' => 'Cambio']))->toThrow(ValidationException::class);
    expect(fn () => $resource->delete())->toThrow(ValidationException::class);
    $this->postJson(route('documento-recursos.store'), ['archivo' => UploadedFile::fake()->image('grande.png', 2049, 20)])->assertUnprocessable()->assertJsonValidationErrors('archivo');
    $this->postJson(route('documento-recursos.store'), ['archivo' => UploadedFile::fake()->createWithContent('falso.png', '<svg onload="alert(1)"/>')])->assertUnprocessable();
    $other = User::factory()->withPersonalTeam()->create();
    $this->actingAs($other)->getJson(route('documento-recursos.index'))->assertForbidden();
    $this->get(route('documento-recursos.show', $resource))->assertForbidden();
    $this->postJson(route('documento-recursos.store'), [])->assertForbidden();
});

test('versiones conservan imágenes presentación e integridad al duplicar', function () {
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    $resource = app(DocumentoRecursoService::class)->upload(UploadedFile::fake()->image('logo.jpg', 40, 20), $user->id);
    $service = app(DocumentoPlantillaVersionService::class);
    $template = $service->create(editorPayload(['encabezado_html' => '<p><img src="https://evil.test" data-recurso-id="'.$resource->id.'" width="160" alt="Logo"></p>']), $user->id);
    $first = $service->activate($template->versiones->first());
    $hash = $first->contenido_hash;
    expect($first->recursos->first()->id)->toBe($resource->id)->and($first->encabezado_html)->not->toContain('src=');
    expect(fn () => $first->update(['presentacion' => ['marca_agua' => 'CAMBIO']]))->toThrow(ValidationException::class);
    $second = $service->duplicate($template, $first, $user->id);
    expect($second->recursos->first()->id)->toBe($resource->id)->and($second->presentacion)->toBe($first->presentacion);
    $service->update($template, $second, editorPayload(['presentacion' => ['marca_agua' => 'COPIA']]));
    expect($first->fresh()->contenido_hash)->toBe($hash)->and($second->fresh()->contenido_hash)->not->toBe($hash);
});

test('rechaza referencias inventadas y marcas de agua con variables', function () {
    $service = app(DocumentoPlantillaVersionService::class);
    expect(fn () => $service->content(DocumentoPlantillaTipo::Contrato, editorPayload(['contenido_html' => '<p>Texto<img data-recurso-id="00000000-0000-0000-0000-000000000000"></p>'])))->toThrow(ValidationException::class);
    expect(fn () => $service->content(DocumentoPlantillaTipo::Contrato, editorPayload(['presentacion' => ['marca_agua' => '{{cliente.nombre_completo}}']])))->toThrow(ValidationException::class);
});

test('previsualizar produce PDF sintético sin guardar documentos ni cambiar la versión', function () {
    $user = actingAsSuperAdmin();
    $template = app(DocumentoPlantillaVersionService::class)->create(editorPayload(), $user->id);
    $version = $template->versiones->first();
    $hash = $version->contenido_hash;
    app()->instance(DocumentoPdfRenderer::class, new class implements DocumentoPdfRenderer
    {
        public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null, array $options = []): string
        {
            expect($bodyHtml)->toContain('María Ejemplo López')->not->toContain('{{');
            expect($options['marca_agua'])->toBe('BORRADOR');

            return "%PDF-1.4\n%%EOF";
        }
    });
    $this->actingAs($user)->get(route('plantillas-documentos.preview-pdf', $version))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->postJson(route('plantillas-documentos.preview-draft'), editorPayload())->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect(DocumentoGenerado::count())->toBe(0)->and($version->fresh()->contenido_hash)->toBe($hash)->and(DocumentoPlantilla::count())->toBe(1);
    $this->actingAs(User::factory()->withPersonalTeam()->create())->get(route('plantillas-documentos.preview-pdf', $version))->assertForbidden();
});

test('error del motor de previsualización no revela rutas ni contenido', function () {
    $user = actingAsSuperAdmin();
    app()->instance(DocumentoPdfRenderer::class, new class implements DocumentoPdfRenderer
    {
        public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null, array $options = []): string
        {
            throw new RuntimeException('/home/private SECRET');
        }
    });
    $this->actingAs($user)->postJson(route('plantillas-documentos.preview-draft'), editorPayload())->assertStatus(503)->assertJsonPath('message', 'No fue posible preparar el PDF. Inténtelo nuevamente.');
});
