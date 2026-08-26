<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Enums\DocumentoPlantillaTipo;
use App\Http\Requests\GuardarDocumentoPlantillaRequest;
use App\Models\DocumentoPlantilla;
use App\Models\DocumentoPlantillaVersion;
use App\Services\ActivityLogService;
use App\Services\Documentos\CatalogoVariablesDocumento;
use App\Services\Documentos\CompiladorPlantillaDocumento;
use App\Services\Documentos\DocumentoPlantillaVersionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DocumentoPlantillaController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:read plantillas-documentos', only: ['index', 'preview']),
            new Middleware('permission:create plantillas-documentos', only: ['store']),
            new Middleware('permission:update plantillas-documentos', only: ['update']),
            new Middleware('permission:version plantillas-documentos', only: ['versionar']),
            new Middleware('permission:activate plantillas-documentos', only: ['activar']),
            new Middleware('permission:retire plantillas-documentos', only: ['retirar']),
        ];
    }

    public function index(CatalogoVariablesDocumento $catalogo)
    {
        $this->authorize('viewAny', DocumentoPlantilla::class);

        return Inertia::render('Documentos/Plantillas/Index', [
            'plantillas' => fn () => DocumentoPlantilla::query()
                ->with(['versiones' => fn ($query) => $query->withCount('documentosGenerados')->orderByDesc('numero')])
                ->orderBy('nombre')->get(),
            'tipos' => collect(DocumentoPlantillaTipo::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()]),
            'variables' => collect(DocumentoPlantillaTipo::cases())->mapWithKeys(fn ($type) => [$type->value => $catalogo->forType($type)]),
        ]);
    }

    public function store(GuardarDocumentoPlantillaRequest $request, DocumentoPlantillaVersionService $service)
    {
        $this->authorize('create', DocumentoPlantilla::class);
        $template = $service->create($request->validated(), Auth::id());
        $this->log(ActivityEvent::DocumentTemplateCreated, 'Plantilla documental creada', $template, ['clave', 'nombre', 'tipo']);

        return back()->with('success', 'Plantilla creada como borrador.');
    }

    public function update(GuardarDocumentoPlantillaRequest $request, DocumentoPlantilla $plantilla, DocumentoPlantillaVersion $version, DocumentoPlantillaVersionService $service)
    {
        $this->authorize('update', $plantilla);
        $service->update($plantilla, $version, $request->validated());
        $this->log(ActivityEvent::DocumentTemplateUpdated, 'Borrador de plantilla actualizado', $plantilla, ['nombre', 'descripcion', 'contenido']);

        return back()->with('success', 'Borrador guardado.');
    }

    public function versionar(DocumentoPlantilla $plantilla, DocumentoPlantillaVersion $version, DocumentoPlantillaVersionService $service)
    {
        $this->authorize('version', $plantilla);
        $new = $service->duplicate($plantilla, $version, Auth::id());
        $this->log(ActivityEvent::DocumentTemplateVersioned, 'Nueva versión de plantilla creada', $plantilla, ['version']);

        return back()->with('success', "Versión {$new->numero} creada como borrador.");
    }

    public function activar(DocumentoPlantillaVersion $version, DocumentoPlantillaVersionService $service)
    {
        $this->authorize('activate', $version);
        $service->activate($version);
        $this->log(ActivityEvent::DocumentTemplateActivated, 'Versión de plantilla activada', $version->plantilla, ['estado', 'version']);

        return back()->with('success', 'Versión activada; su contenido quedó protegido contra cambios.');
    }

    public function retirar(DocumentoPlantillaVersion $version, DocumentoPlantillaVersionService $service)
    {
        $this->authorize('retire', $version);
        $service->retire($version);
        $this->log(ActivityEvent::DocumentTemplateRetired, 'Versión de plantilla retirada', $version->plantilla, ['estado', 'version']);

        return back()->with('success', 'Versión retirada; el historial se conserva.');
    }

    public function preview(DocumentoPlantillaVersion $version, CompiladorPlantillaDocumento $compiler)
    {
        $version->loadMissing('plantilla');
        $this->authorize('view', $version->plantilla);
        $values = $this->previewValues();

        return response()->json([
            'header' => $compiler->render($version->encabezado_html ?? '', $values),
            'body' => $compiler->render($version->contenido_html, $values),
            'footer' => $compiler->render($version->pie_html ?? '', $values),
            'notice' => 'Previsualización con datos sintéticos. No es el PDF final.',
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    private function previewValues(): array
    {
        return [
            'documento.fecha_generacion' => '23/08/2026', 'empresa.razon_social' => 'Financiera Ejemplo, S.A. de C.V.',
            'empresa.nombre_comercial' => 'Financiera Ejemplo', 'empresa.rfc' => 'FEX010101AB1',
            'empresa.telefono' => '+52 55 0000 0000', 'empresa.email' => 'contacto@ejemplo.test',
            'cliente.nombre_completo' => 'María Ejemplo López', 'cliente.rfc' => 'EILM900101AB1',
            'cliente.curp' => 'EILM900101MDFJPR01', 'cliente.fecha_nacimiento' => '01/01/1990',
            'cliente.telefono' => '+52 55 1111 2222', 'cliente.email' => 'cliente@ejemplo.test',
            'garantia.tipo' => 'Prendaria', 'garantia.descripcion' => 'Bien de prueba para previsualización',
            'garantia.valor_estimado' => '$25,000.00', 'garantia.moneda' => 'MXN',
            'garantia.propietario' => 'María Ejemplo López',
        ];
    }

    private function log(ActivityEvent $event, string $description, DocumentoPlantilla $template, array $fields): void
    {
        app(ActivityLogService::class)->log($event, $description, $template, ['changed_fields' => $fields], Auth::user());
    }
}
