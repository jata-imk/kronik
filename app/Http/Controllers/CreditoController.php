<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarDesembolsoRequest;
use App\Models\Credito;
use App\Models\Solicitud;
use App\Models\Sucursal;
use App\Services\Credito\DesembolsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CreditoController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Credito::class);
        $filters = $request->validate(['buscar' => 'nullable|string|max:100', 'sucursal_id' => 'nullable|integer|exists:sucursales,id',
            'por_pagina' => ['nullable', 'integer', Rule::in([10, 25, 50])], 'orden' => ['nullable', Rule::in(['recientes', 'antiguos'])]]);
        $creditos = Credito::with(['cliente:id,primer_nombre,apellido_paterno,apellido_materno', 'sucursal:id,nombre', 'responsable:id,name'])
            ->when($filters['sucursal_id'] ?? null, fn ($q, $id) => $q->where('sucursal_id', $id))
            ->when($filters['buscar'] ?? null, fn ($q, $buscar) => $q->where(fn ($q) => $q
                ->where('id', ctype_digit($buscar) ? $buscar : 0)
                ->orWhereHas('cliente', fn ($c) => $c->where('primer_nombre', 'like', '%'.$buscar.'%')->orWhere('apellido_paterno', 'like', '%'.$buscar.'%'))))
            ->orderBy('id', ($filters['orden'] ?? 'recientes') === 'antiguos' ? 'asc' : 'desc')->paginate($filters['por_pagina'] ?? 25)->withQueryString();

        return Inertia::render('Creditos/Index', ['creditos' => $creditos, 'filters' => $filters, 'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre'])]);
    }

    public function preparar(Solicitud $solicitud, DesembolsoService $service)
    {
        Gate::authorize('viewAny', Credito::class);
        Gate::authorize('viewPackage', $solicitud);
        if ($credito = $solicitud->credito) {
            return redirect()->route('creditos.show', $credito);
        }

        return Inertia::render('Creditos/Desembolso', [
            'solicitud' => $solicitud->load(['cliente:id,primer_nombre,apellido_paterno', 'sucursal:id,nombre']),
            'preparacion' => $service->requisitos($solicitud, request()->user()),
            'puedeRegistrar' => Gate::allows('disburse', $solicitud),
        ]);
    }

    public function store(RegistrarDesembolsoRequest $request, Solicitud $solicitud, DesembolsoService $service)
    {
        $credito = $service->registrar($solicitud, $request->validated(), $request->user());

        return redirect()->route('creditos.show', $credito)->with('success', 'Desembolso QA registrado una sola vez. No se ejecutó una transferencia bancaria.');
    }

    public function show(Credito $credito, \App\Services\Credito\PagoService $pagos)
    {
        Gate::authorize('view', $credito);
        $credito->load(['cliente:id,primer_nombre,apellido_paterno,apellido_materno', 'sucursal:id,nombre', 'responsable:id,name']);
        $desembolso = $credito->desembolso;
        $cronograma = $credito->cronogramas()->where('version', 1)->firstOrFail();

        return Inertia::render('Creditos/Show', [
            'credito' => $credito,
            'desembolso' => [...$desembolso->only(['id', 'importe', 'fecha_efectiva', 'created_at', 'registrado_por', 'medio']), 'referencia' => $desembolso->referencia],
            'cronograma' => ['version' => $cronograma->version, 'snapshot_hash' => $cronograma->snapshot_hash, 'tabla' => $cronograma->snapshot],
            'movimientos' => $credito->movimientos()->latest('id')->paginate(25),
            'pagos' => $credito->pagos()->latest('id')->paginate(10, ['id', 'credito_id', 'tipo', 'importe', 'fecha_efectiva', 'created_at', 'reversa_de'], 'pagos_page'),
            'situacion' => $pagos->consultar($credito),
            'puedePagar' => Gate::allows('pay', $credito),
            'puedeVerContrato' => Gate::allows('viewPackage', $credito->solicitud),
        ]);
    }
}
