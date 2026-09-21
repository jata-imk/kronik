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
        $filters = $request->validate([
            'buscar' => 'nullable|string|max:100',
            'estado' => ['nullable', Rule::enum(SolicitudEstado::class)],
            'por_pagina' => ['nullable', 'integer', Rule::in([10, 25, 50])],
            'orden' => ['nullable', Rule::in(['recientes', 'antiguas'])],
        ]);
        $solicitudes = Solicitud::query()->with($this->relations())
            ->when($mine, fn ($q) => $q->where('responsable_id', $request->user()->id))
            ->when($filters['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filters['buscar'] ?? null, fn ($q, $buscar) => $q->whereHas('cliente', fn ($c) => $c->where(fn ($n) => $n
                ->where('primer_nombre', 'like', '%'.$buscar.'%')->orWhere('apellido_paterno', 'like', '%'.$buscar.'%'))))
            ->orderBy('id', ($filters['orden'] ?? 'recientes') === 'antiguas' ? 'asc' : 'desc')
            ->paginate($filters['por_pagina'] ?? 25)->withQueryString();

        return Inertia::render('Solicitudes/Index', [
            'solicitudes' => $solicitudes, 'filters' => $filters, 'miTrabajo' => $mine,
            'puedeCrear' => Gate::allows('create', Solicitud::class),
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

        return Inertia::render('Solicitudes/Show', [
            'solicitud' => $solicitud,
            'revision' => $solicitud->revisiones()->latest('numero')->first(),
            'can' => [
                'update' => Gate::allows('update', $solicitud),
                'assign' => Gate::allows('assign', $solicitud),
                'sic' => Gate::allows('read historial-crediticio'),
            ],
            'responsables' => Gate::allows('assign', $solicitud) ? User::query()->where('status', UserStatus::Active)
                ->whereHas('sucursales', fn ($q) => $q->whereKey($solicitud->sucursal_id))
                ->orderBy('name')->get(['id', 'name', 'is_super_admin'])
                ->filter(fn ($user) => Gate::forUser($user)->allows('viewAny', Solicitud::class))
                ->map(fn ($user) => $user->only(['id', 'name']))->values() : [],
        ]);
    }

    public function edit(Solicitud $solicitud)
    {
        Gate::authorize('update', $solicitud);
        if ($solicitud->estado !== SolicitudEstado::Borrador) {
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
