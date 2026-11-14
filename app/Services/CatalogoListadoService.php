<?php

namespace App\Services;

use App\Models\DocumentoPlantilla;
use App\Models\ProductoCrediticio;
use Illuminate\Http\Request;

final class CatalogoListadoService
{
    public function __construct(private ListadoParametros $params) {}

    private function filtros(): array
    {
        return ['global' => fn ($q, $v) => $q->where(fn ($q) => $q->where('nombre', 'like', '%'.$v.'%')->orWhere('clave', 'like', '%'.$v.'%'))];
    }

    public function productos(Request $request): array
    {
        $request->validate(['producto_id' => 'nullable|integer|min:1', 'version_id' => 'nullable|integer|min:1', 'modo' => ['nullable', \Illuminate\Validation\Rule::in(['consulta', 'edicion'])], 'versiones_page' => 'nullable|integer|min:1', 'versiones_por_pagina' => ['nullable', 'integer', \Illuminate\Validation\Rule::in([10, 25, 50])]]);
        $filters = $this->filtros();
        $sorts = ['nombre' => 'nombre', 'clave' => 'clave'];
        $values = $this->params->resolver($request, $filters, $sorts);
        $relations = ['periodicidades', 'reglas', 'comisiones.concepto', 'politicaActual:originacion_politicas.id,originacion_politicas.producto_version_id,originacion_politicas.numero,originacion_politicas.created_at'];
        $page = $this->params->aplicar(ProductoCrediticio::withCount('versiones')->with(['ultimaVersion' => fn ($q) => $q->with($relations)->withCount('usos')]), $values, $filters, $sorts, 'nombre')->paginate($values['por_pagina'])->withQueryString();
        $versions = fn ($query) => $query->with($relations)->withCount('usos');
        foreach ($page as $product) {
            $product->setRelation('versiones', collect([$product->ultimaVersion])->filter()->values());
            $product->unsetRelation('ultimaVersion');
        }
        $id = $request->input('producto_id', $page->first()?->id);
        $selected = $id ? ProductoCrediticio::findOrFail($id) : null;
        $versionPage = $selected ? $versions($selected->versiones())->orderByDesc('numero')->paginate($request->integer('versiones_por_pagina', 25), ['*'], 'versiones_page')->withQueryString() : null;
        if ($selected) {
            $selected->setRelation('versiones', $versionPage->getCollection());
            $selected->setAttribute('ultimaVersion', $versions($selected->versiones())->orderByDesc('numero')->first());
            $selected->setAttribute('versionSeleccionada', $request->filled('version_id') ? $versions($selected->versiones())->findOrFail($request->integer('version_id')) : null);
        }
        $nombres = $page->pluck('nombre', 'id');
        if ($selected) {
            $nombres->put($selected->id, $selected->nombre);
        }
        foreach ($page->getCollection()->pluck('versiones')->flatten()->concat($selected?->versiones ?? collect())->push($selected?->ultimaVersion)->push($selected?->versionSeleccionada)->filter() as $version) {
            $version->setAttribute('capacidades', \App\Services\Credito\CapacidadesProducto::paraVersion($version));
            $version->setAttribute('puede_editar', $request->user()->can('update productos-crediticios') && $version->estado === \App\Enums\ProductoVersionEstado::Borrador && $version->usos_count === 0);
            $version->setAttribute('originacion', app(OriginacionPoliticaService::class)->resumen($version, $request->user(), $nombres->get($version->producto_crediticio_id)));
        }

        return ['productos' => $page->getCollection(), 'productoSeleccionado' => $selected,
            'modoVersion' => $request->input('modo'),
            'listado' => $this->params->metadata($page, $values),
            'versionesListado' => $versionPage ? $this->params->metadata($versionPage, []) : null];
    }

    public function plantillas(Request $request): array
    {
        $request->validate(['plantilla_id' => 'nullable|integer|min:1', 'modo' => ['nullable', \Illuminate\Validation\Rule::in(['consulta', 'edicion'])], 'version_id' => 'nullable|integer|min:1', 'versiones_page' => 'nullable|integer|min:1', 'versiones_por_pagina' => ['nullable', 'integer', \Illuminate\Validation\Rule::in([10, 25, 50])]]);
        $filters = [...$this->filtros(), 'tipo' => fn ($q, $v) => $q->where('tipo', $v)];
        $sorts = ['nombre' => 'nombre', 'clave' => 'clave'];
        $values = $this->params->resolver($request, $filters, $sorts);
        $query = $this->params->aplicar(DocumentoPlantilla::withCount('versiones')->with(['ultimaVersion' => fn ($q) => $q->withCount('documentosGenerados'), 'versionActiva' => fn ($q) => $q->withCount('documentosGenerados')]), $values, $filters, $sorts, 'nombre');
        $ids = (clone $query)->reorder()->select('documento_plantillas.id');
        $summary = [
            'activas' => (clone $query)->whereHas('versiones', fn ($q) => $q->where('estado', 'activa'))->count(),
            'borradores' => \App\Models\DocumentoPlantillaVersion::whereIn('documento_plantilla_id', clone $ids)->where('estado', 'borrador')->count(),
            'generados' => \App\Models\DocumentoGenerado::whereHas('version', fn ($q) => $q->whereIn('documento_plantilla_id', clone $ids))->count(),
        ];
        $page = $query->paginate($values['por_pagina'])->withQueryString();
        foreach ($page as $template) {
            $template->setRelation('versiones', collect([$template->versionActiva ?? $template->ultimaVersion])->filter()->values());
            $template->unsetRelation('versionActiva')->unsetRelation('ultimaVersion');
        }
        $id = $request->input('plantilla_id', $page->first()?->id);
        $selected = $id ? DocumentoPlantilla::findOrFail($id) : null;
        $versionPage = $selected?->versiones()->withCount('documentosGenerados')->orderByDesc('numero')->paginate($request->integer('versiones_por_pagina', 25), ['*'], 'versiones_page')->withQueryString();
        if ($selected) {
            $selected->setRelation('versiones', $versionPage->getCollection());
            $preferred = $selected->versiones()->withCount('documentosGenerados')->orderByRaw("case when estado = 'activa' then 0 else 1 end")->orderByDesc('numero')->first();
            $selected->setAttribute('versionPreferida', $preferred);
            $selected->setAttribute('versionSeleccionada', $request->filled('version_id') ? $selected->versiones()->withCount('documentosGenerados')->findOrFail($request->integer('version_id')) : $preferred);
        }

        foreach (collect($selected?->versiones ?? [])->push($selected?->versionSeleccionada)->push($selected?->versionPreferida)->filter() as $version) {
            $version->setAttribute('puede_editar', $request->user()->can('update plantillas-documentos') && $version->estado === \App\Enums\DocumentoPlantillaVersionEstado::Borrador && $version->documentos_generados_count === 0);
        }

        return ['modoVersion' => $request->input('modo'), 'resumenListado' => $summary, 'plantillas' => $page->getCollection(), 'plantillaSeleccionada' => $selected,
            'listado' => $this->params->metadata($page, $values),
            'versionesListado' => $versionPage ? $this->params->metadata($versionPage, []) : null];
    }
}
