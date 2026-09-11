<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Models\DocumentoRecurso;
use App\Services\ActivityLogService;
use App\Services\Documentos\DocumentoRecursoService;
use Illuminate\Http\Request;

class DocumentoRecursoController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('read plantillas-documentos'), 403);

        return DocumentoRecurso::query()->when($request->string('q')->trim()->value(), fn ($query, $q) => $query->where('nombre', 'like', '%'.mb_substr($q, 0, 160).'%'))
            ->latest()->paginate(24)->withQueryString();
    }

    public function store(Request $request, DocumentoRecursoService $service, ActivityLogService $activity)
    {
        abort_unless($request->user()->can('read plantillas-documentos') && ($request->user()->can('create plantillas-documentos') || $request->user()->can('update plantillas-documentos')), 403);
        $request->validate(['archivo' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:2048', 'dimensions:max_width=2048,max_height=2048']], [], ['archivo' => 'imagen']);

        $resource = $service->upload($request->file('archivo'), $request->user()->id);
        // Activity subjects use numeric morph IDs; resources use UUIDs.
        $activity->log(ActivityEvent::DocumentResourceCreated, 'Imagen documental incorporada', null, [
            'related' => ['type' => 'documento_recurso', 'id' => $resource->id],
        ], $request->user());

        return response()->json($resource, 201);
    }

    public function show(Request $request, DocumentoRecurso $recurso, DocumentoRecursoService $service)
    {
        abort_unless($request->user()->can('read plantillas-documentos'), 403);

        return response($service->bytes($recurso), 200, [
            'Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
