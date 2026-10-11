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
            'habilitado' => config('sic.qa_habilitado') === true && config('sic.sandbox') === true,
            'servicios' => collect(config('sic.servicios'))->map(fn ($v, $key) => ['clave' => $key, 'nombre' => $v['nombre']])->values(),
            'consentimientos' => $selected?->consentimientosSic()->whereNull('revocado_en')->get(['id', 'otorgado_en', 'vence_en']) ?? [],
            'revisiones' => $selected ? \App\Models\SolicitudRevision::whereIn('solicitud_id', \App\Models\Solicitud::where('cliente_id', $selected->id)->select('id'))
                ->whereRaw('numero = (select max(r.numero) from solicitud_revisiones r where r.solicitud_id = solicitud_revisiones.solicitud_id)')
                ->get(['id', 'solicitud_id', 'numero']) : [],
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

        if (config('sic.qa_habilitado') !== true) {
            ConsultaSicNoDisponible::rechazar();
        }
        $validated = app(\App\Http\Requests\ConsultarSicRequest::class)->validated();
        $consulta = app(\App\Services\SICs\SicOperacionService::class)->preparar($selected, $validated, $request->user());

        return redirect()->route('consultas-sic.show', $consulta)->with('success', 'Consulta SIC QA preparada. Revisa su resultado antes de continuar.');
    }

    public function clientes(Request $request)
    {
        Gate::authorize('create circulo-credito');
        Gate::authorize('viewAny', Cliente::class);
        $data = $request->validate(['buscar' => ['required', 'string', 'min:2', 'max:100']]);

        return response()->json(Cliente::where('sucursal_id', $request->user()->current_sucursal_id)
            ->where(fn ($q) => $q->where('primer_nombre', 'like', '%'.$data['buscar'].'%')->orWhere('apellido_paterno', 'like', '%'.$data['buscar'].'%'))
            ->orderBy('id')->limit(20)->get(['id', 'primer_nombre', 'apellido_paterno', 'apellido_materno']))->header('Cache-Control', 'private, no-store');
    }
}
