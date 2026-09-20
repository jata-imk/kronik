<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\SICs\ConsultaSicNoDisponible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CirculoCreditoController extends Controller
{
    public function create(Request $request, ?string $cliente = null)
    {
        Gate::authorize('create circulo-credito');
        Gate::authorize('viewAny', Cliente::class);
        $selected = $cliente ? Cliente::findOrFail($cliente) : null;
        if ($selected) {
            Gate::authorize('update', $selected);
        }

        return Inertia::render('HistorialCrediticio/CirculoDeCredito/Create', [
            'cliente' => $selected?->only(['id', 'primer_nombre', 'apellido_paterno', 'apellido_materno']),
            'motivoNoDisponible' => ConsultaSicNoDisponible::MENSAJE,
        ]);
    }

    public function store(Request $request, ?string $cliente = null)
    {
        Gate::authorize('create circulo-credito');
        Gate::authorize('viewAny', Cliente::class);
        $data = $request->validate([
            'cliente' => $cliente ? 'nullable' : 'required|integer|exists:clientes,id',
        ], [
            'cliente.required' => 'Selecciona un cliente.',
            'cliente.integer' => 'Selecciona un cliente válido.',
            'cliente.exists' => 'El cliente seleccionado no está disponible.',
        ]);
        $selected = Cliente::findOrFail($cliente ?: $data['cliente']);
        Gate::authorize('update', $selected);

        // No flag may reopen the unvalidated, sandbox-only adapters.
        ConsultaSicNoDisponible::rechazar();
    }
}
