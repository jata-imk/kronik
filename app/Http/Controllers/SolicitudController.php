<?php

namespace App\Http\Controllers;

use App\Enums\SolicitudEstado;
use App\Enums\UserStatus;
use App\Http\Requests\GuardarSolicitudRequest;
use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\FechaEmpresa;
use App\Services\SolicitudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SolicitudController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Solicitud::class);
        $mine = $request->routeIs('solicitudes.trabajo');
        $bandeja = null;
        if ($request->routeIs('cumplimiento.index')) {
            Gate::authorize('read cumplimiento');
            $bandeja = 'Cumplimiento';
        } elseif ($request->routeIs('evaluacion-solicitudes.index')) {
            Gate::authorize('read evaluacion-solicitudes');
            $bandeja = 'Evaluación';
        }
        $filters = $request->validate([
            'buscar' => 'nullable|string|max:100',
            'estado' => ['nullable', Rule::enum(SolicitudEstado::class)],
            'por_pagina' => ['nullable', 'integer', Rule::in([10, 25, 50])],
            'orden' => ['nullable', Rule::in(['recientes', 'antiguas'])],
        ]);
        if ($bandeja && ! $request->has('estado')) {
            $filters['estado'] = SolicitudEstado::EnRevision->value;
        }
        $solicitudes = Solicitud::query()->with($this->relations())
            ->when($mine, fn ($q) => $q->where('responsable_id', $request->user()->id))
            ->when($mine && empty($filters['estado']), fn ($q) => $q->whereNotIn('estado', [SolicitudEstado::Rechazada, SolicitudEstado::Cancelada]))
            ->when($filters['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filters['buscar'] ?? null, fn ($q, $buscar) => $q->whereHas('cliente', fn ($c) => $c->where(fn ($n) => $n
                ->where('primer_nombre', 'like', '%'.$buscar.'%')->orWhere('apellido_paterno', 'like', '%'.$buscar.'%'))))
            ->orderBy('id', ($filters['orden'] ?? 'recientes') === 'antiguas' ? 'asc' : 'desc')
            ->paginate($filters['por_pagina'] ?? 25)->withQueryString();

        return Inertia::render('Solicitudes/Index', [
            'solicitudes' => $solicitudes, 'filters' => $filters, 'miTrabajo' => $mine,
            'puedeCrear' => ! $bandeja && Gate::allows('create', Solicitud::class),
            'bandeja' => $bandeja, 'rutaBandeja' => $request->route()->getName(),
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Solicitud::class);
        $data = $request->validate(['cliente_id' => 'nullable|integer|exists:clientes,id']);
        $cliente = isset($data['cliente_id']) ? Cliente::findOrFail($data['cliente_id']) : null;
        if ($cliente) {
            Gate::authorize('view', $cliente);
        }

        return Inertia::render('Solicitudes/Form', ['solicitud' => null, 'productos' => $this->productos(),
            'clienteInicial' => $cliente?->only(['id', 'primer_nombre', 'apellido_paterno', 'apellido_materno'])]);
    }

    public function clientes(Request $request)
    {
        Gate::authorize('create', Solicitud::class);
        $data = $request->validate(['buscar' => 'required|string|min:2|max:100']);

        return Cliente::query()->where('sucursal_id', $request->user()->current_sucursal_id)
            ->where(fn ($q) => $q->where('primer_nombre', 'like', '%'.$data['buscar'].'%')
                ->orWhere('apellido_paterno', 'like', '%'.$data['buscar'].'%'))
            ->orderBy('primer_nombre')->orderBy('id')->limit(20)
            ->get(['id', 'primer_nombre', 'apellido_paterno', 'apellido_materno']);
    }

    public function store(GuardarSolicitudRequest $request, SolicitudService $service)
    {
        $solicitud = $service->crear($request->validated(), $request->user());

        return redirect()->route('solicitudes.show', $solicitud);
    }

    public function show(Solicitud $solicitud)
    {
        Gate::authorize('view', $solicitud);
        $solicitud->load($this->relations());
        $solicitud->load(['eventos' => fn ($q) => $q->latest('id')->limit(30)->with('actor:id,name')]);
        $revision = $solicitud->revisiones()->latest('numero')->first();
        $dictamenes = [];
        foreach (['evaluacion' => 'viewEvaluation', 'pld' => 'viewCompliance'] as $tipo => $ability) {
            $dictamenes[$tipo] = Gate::allows($ability, $solicitud)
                ? $solicitud->dictamenes()->where('tipo', $tipo)->with('actor:id,name')->latest('id')->limit(20)->get()
                    ->map(fn ($item) => [...$item->toArray(), 'contenido' => $item->contenido,
                        'revision_actual' => $item->solicitud_revision_id === $revision?->id && $solicitud->estado === SolicitudEstado::EnRevision])
                : null;
        }

        return Inertia::render('Solicitudes/Show', [
            'solicitud' => $solicitud,
            'revision' => $revision,
            'dictamenes' => $dictamenes,
            'resoluciones' => $solicitud->resoluciones()->with('actor:id,name')->latest('id')->limit(30)->get()
                ->map(fn ($item) => [...$item->toArray(), 'motivo' => $item->motivo]),
            'can' => [
                'update' => Gate::allows('update', $solicitud),
                'assign' => Gate::allows('assign', $solicitud),
                'sic' => Gate::allows('read historial-crediticio'),
                'review' => Gate::allows('review', $solicitud),
                'cancel' => Gate::allows('cancel', $solicitud),
                'evaluate' => Gate::allows('evaluate', $solicitud),
                'compliance' => Gate::allows('compliance', $solicitud),
            ],
            'responsables' => (Gate::allows('assign', $solicitud) || Gate::allows('review', $solicitud)) ? User::query()->where('status', UserStatus::Active)
                ->whereHas('sucursales', fn ($q) => $q->whereKey($solicitud->sucursal_id))
                ->orderBy('name')->get(['id', 'name', 'is_super_admin'])
                ->filter(fn ($user) => Gate::forUser($user)->allows('viewAny', Solicitud::class))
                ->map(fn ($user) => $user->only(['id', 'name']))->values() : [],
        ]);
    }

    public function edit(Solicitud $solicitud)
    {
        Gate::authorize('update', $solicitud);
        if (! $solicitud->estado->editable()) {
            return redirect()->route('solicitudes.show', $solicitud);
        }

        return Inertia::render('Solicitudes/Form', [
            'solicitud' => $solicitud->load($this->relations()), 'productos' => $this->productos(),
        ]);
    }

    public function update(GuardarSolicitudRequest $request, Solicitud $solicitud, SolicitudService $service)
    {
        $service->guardar($solicitud, $request->validated(), $request->user());

        return redirect()->route('solicitudes.show', $solicitud);
    }

    public function enviar(Request $request, Solicitud $solicitud, SolicitudService $service)
    {
        Gate::authorize('update', $solicitud);
        $data = $request->validate(['lock_version' => 'required|integer|min:0']);
        $service->enviar($solicitud, $data['lock_version'], $request->user());

        return back();
    }

    public function asignar(Request $request, Solicitud $solicitud, SolicitudService $service)
    {
        Gate::authorize('assign', $solicitud);
        $data = $request->validate(['lock_version' => 'required|integer|min:0', 'responsable_id' => 'required|integer|exists:users,id']);
        $service->asignar($solicitud, $data['lock_version'], $data['responsable_id'], $request->user());

        return back();
    }

    public function resolver(Request $request, Solicitud $solicitud, SolicitudService $service)
    {
        $service->resolver($solicitud, $request->only(['accion', 'motivo', 'lock_version', 'responsable_id']), $request->user());

        return back();
    }

    public function dictaminar(Request $request, Solicitud $solicitud, SolicitudService $service)
    {
        $service->dictaminar($solicitud, $request->only(['tipo_dictamen', 'resultado', 'fundamento', 'fuentes', 'metodologia', 'nivel_riesgo', 'lock_version']), $request->user());

        return back();
    }

    private function productos()
    {
        return ProductoVersion::disponiblesParaOriginacion(app(FechaEmpresa::class)->hoy())
            ->whereHas('producto', fn ($q) => $q->where('activo', true))
            ->with(['producto:id,nombre,clave', 'periodicidades', 'reglas'])
            ->orderBy('producto_crediticio_id')->get();
    }

    private function relations(): array
    {
        return ['cliente:id,primer_nombre,apellido_paterno,apellido_materno',
            'sucursal:id,nombre', 'responsable:id,name', 'productoVersion.producto:id,nombre'];
    }
}
