<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\SicQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class HistorialCrediticioController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('read historial-crediticio');
        Gate::authorize('viewAny', Cliente::class);

        return $this->render($request);
    }

    public function show(Request $request, Cliente $cliente)
    {
        Gate::authorize('read historial-crediticio');
        Gate::authorize('view', $cliente);

        return $this->render($request, $cliente);
    }

    private function render(Request $request, ?Cliente $cliente = null)
    {
        $filters = $request->validate([
            'buscar' => 'nullable|string|max:100',
            'estado' => ['nullable', Rule::in(['pending', 'success', 'error'])],
            'por_pagina' => ['nullable', 'integer', Rule::in([10, 25, 50])],
        ], [
            'estado.in' => 'Selecciona un estado de consulta válido.',
            'por_pagina.in' => 'Selecciona 10, 25 o 50 registros por página.',
        ]);
        $queries = SicQuery::query()
            ->select(['id', 'cliente_id', 'sic_id', 'sic_api_id', 'fecha_consulta', 'status'])
            ->with([
                'cliente:id,primer_nombre,apellido_paterno,apellido_materno',
                'sic:id,nombre',
                'api:id,nombre',
            ])
            ->when($cliente, fn ($q) => $q->where('cliente_id', $cliente->id))
            ->when($filters['estado'] ?? null, fn ($q, $estado) => $q->where('status', $estado))
            ->when($filters['buscar'] ?? null, fn ($q, $buscar) => $q->whereHas('cliente', fn ($c) => $c
                ->where(fn ($names) => $names->where('primer_nombre', 'like', '%'.$buscar.'%')
                    ->orWhere('apellido_paterno', 'like', '%'.$buscar.'%')
                    ->orWhere('apellido_materno', 'like', '%'.$buscar.'%'))))
            ->orderByDesc('fecha_consulta')->orderByDesc('id')
            ->paginate($filters['por_pagina'] ?? 25)->withQueryString();

        return Inertia::render('HistorialCrediticio/Index', [
            'cliente' => $cliente?->only(['id', 'primer_nombre', 'apellido_paterno', 'apellido_materno']),
            'consultas' => $queries,
            'filters' => $filters,
            'puedeConsultar' => Gate::allows('create circulo-credito')
                && (! $cliente || Gate::allows('update', $cliente)),
        ]);
    }
}
