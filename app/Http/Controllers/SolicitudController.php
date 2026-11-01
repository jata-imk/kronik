<?php

namespace App\Http\Controllers;

use App\Enums\SolicitudEstado;
use App\Http\Requests\GuardarSolicitudRequest;
use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
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
        $consulta = app(\App\Services\ConsultaSucursal::class);
        $scope = $consulta->resolver($request);
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
        $solicitudes = $consulta->aplicar(Solicitud::query(), $scope)->with($this->relations())
            ->when($mine, fn ($q) => $q->where('responsable_id', $request->user()->id))
            ->when($mine && empty($filters['estado']), fn ($q) => $q->whereNotIn('estado', [SolicitudEstado::Rechazada, SolicitudEstado::Cancelada, SolicitudEstado::Desembolsada]))
            ->when($filters['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filters['buscar'] ?? null, fn ($q, $buscar) => $q->where(fn ($b) => $b->where('folio', 'like', '%'.$buscar.'%')
                ->orWhereHas('cliente', fn ($c) => $c->where(fn ($n) => $n
                    ->where('primer_nombre', 'like', '%'.$buscar.'%')->orWhere('apellido_paterno', 'like', '%'.$buscar.'%')))))
            ->orderBy('id', ($filters['orden'] ?? 'recientes') === 'antiguas' ? 'asc' : 'desc')
            ->paginate($filters['por_pagina'] ?? 25)->withQueryString();

        return Inertia::render('Solicitudes/Index', [
            'solicitudes' => $solicitudes, 'filters' => [...$filters, ...$scope], 'sucursales' => $consulta->opciones(), 'miTrabajo' => $mine,
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
            abort_unless($request->user()->current_sucursal_id !== null
                && (int) $request->user()->current_sucursal_id === (int) $cliente->sucursal_id, 403);
        }

        return Inertia::render('Solicitudes/Form', ['solicitud' => null, 'productos' => $this->productos(), 'capacidadesCredito' => \App\Services\Credito\CatalogoCapacidadesCredito::paraVue(),
            'clienteInicial' => $cliente?->only(['id', 'primer_nombre', 'apellido_paterno', 'apellido_materno'])]);
    }

    public function clientes(Request $request)
    {
        Gate::authorize('create', Solicitud::class);
        $data = $request->validate(['buscar' => 'required|string|min:2|max:100']);

        if (! $request->user()->currentSucursal?->activa) {
            return response()->json([]);
        }

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
        $ultimaAprobacion = $solicitud->estado === SolicitudEstado::Aprobada
            ? $solicitud->resoluciones()->where('accion', 'aprobar')->latest('id')->first() : null;
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
            'creditoId' => Gate::allows('viewAny', \App\Models\Credito::class) ? $solicitud->credito()->value('id') : null,
            'revision' => $revision,
            'dictamenes' => $dictamenes,
            'aprobacionVencida' => $ultimaAprobacion && $ultimaAprobacion->vigente_hasta->toDateString() < app(FechaEmpresa::class)->hoy()->toDateString(),
            'aprobacion' => collect(app(\App\Services\SolicitudRequisitosService::class)->evaluar($solicitud, auth()->user()))->only(['requisitos', 'puede_aprobar', 'politica']),
            'resoluciones' => $solicitud->resoluciones()->with('actor:id,name')->latest('id')->limit(30)->get()
                ->map(fn ($item) => [...$item->toArray(), 'motivo' => $item->motivo]),
            'can' => [
                'update' => auth()->user()->operaEnSucursal($solicitud->sucursal_id) && Gate::allows('update', $solicitud),
                'assign' => auth()->user()->operaEnSucursal($solicitud->sucursal_id) && Gate::allows('assign', $solicitud),
                'sic' => Gate::allows('read historial-crediticio'),
                'review' => auth()->user()->operaEnSucursal($solicitud->sucursal_id) && Gate::allows('review', $solicitud),
                'cancel' => auth()->user()->operaEnSucursal($solicitud->sucursal_id) && Gate::allows('cancel', $solicitud),
                'evaluate' => auth()->user()->operaEnSucursal($solicitud->sucursal_id) && Gate::allows('evaluate', $solicitud),
                'compliance' => auth()->user()->operaEnSucursal($solicitud->sucursal_id) && Gate::allows('compliance', $solicitud),
                'approve' => auth()->user()->operaEnSucursal($solicitud->sucursal_id) && Gate::allows('approve', $solicitud),
                'paquete' => Gate::allows('viewPackage', $solicitud),
                'desembolso' => Gate::allows('viewAny', \App\Models\Credito::class) && Gate::allows('viewPackage', $solicitud),
                'politica' => Gate::check(['read productos-crediticios', 'manage origination productos-crediticios']),
            ],
            'responsables' => auth()->user()->operaEnSucursal($solicitud->sucursal_id) && (Gate::allows('assign', $solicitud) || Gate::allows('review', $solicitud))
                ? app(\App\Services\SolicitudResponsablesService::class)->buscar($solicitud, auth()->user()) : [],
        ]);
    }

    public function responsables(Request $request, Solicitud $solicitud, \App\Services\SolicitudResponsablesService $service)
    {
        Gate::authorize('view', $solicitud);
        $data = $request->validate(['buscar' => 'nullable|string|max:100']);

        return $service->buscar($solicitud, $request->user(), $data['buscar'] ?? '');
    }

    public function edit(Solicitud $solicitud)
    {
        Gate::authorize('update', $solicitud);
        abort_unless(auth()->user()->operaEnSucursal($solicitud->sucursal_id), 403, 'Selecciona la sucursal responsable de la solicitud.');
        if (! $solicitud->estado->editable()) {
            return redirect()->route('solicitudes.show', $solicitud);
        }

        return Inertia::render('Solicitudes/Form', [
            'solicitud' => $solicitud->load($this->relations()), 'productos' => $this->productos(), 'capacidadesCredito' => \App\Services\Credito\CatalogoCapacidadesCredito::paraVue(),
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

    public function aprobar(Request $request, Solicitud $solicitud, SolicitudService $service)
    {
        Gate::authorize('approve', $solicitud);
        $data = $request->validate(['lock_version' => 'required|integer|min:0', 'motivo' => 'required|string|min:10|max:2000']);
        $service->aprobar($solicitud, $data['lock_version'], $data['motivo'], $request->user());

        return back();
    }

    private function productos()
    {
        return ProductoVersion::disponiblesParaOriginacion(app(FechaEmpresa::class)->hoy())
            ->whereHas('producto', fn ($q) => $q->where('activo', true))
            ->with(['producto:id,nombre,clave', 'periodicidades', 'reglas'])
            ->orderBy('producto_crediticio_id')->get()->map(fn ($version) => $version->setAttribute('capacidades', \App\Services\Credito\CapacidadesProducto::paraVersion($version)));
    }

    private function relations(): array
    {
        return ['cliente:id,primer_nombre,apellido_paterno,apellido_materno',
            'sucursal:id,nombre', 'responsable:id,name', 'productoVersion.producto:id,nombre'];
    }
}
