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
        $consultaSucursal = app(\App\Services\ConsultaSucursal::class);
        $scope = $consultaSucursal->resolver($request, $cliente ? 'all' : 'current');
        $queries = $consultaSucursal->aplicar(SicQuery::query(), $scope)
            ->select(['id', 'cliente_id', 'sic_id', 'sic_api_id', 'fecha_consulta', 'status', 'sucursal_id', 'ambiente', 'escenario_clave', 'procedencia'])
            ->with([
                'cliente:id,primer_nombre,apellido_paterno,apellido_materno',
                'sucursal:id,nombre',
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
            'filters' => [...$filters, ...$scope],
            'sucursales' => $consultaSucursal->opciones(),
            'puedeConsultar' => $request->user()->currentSucursal?->activa && Gate::allows('create circulo-credito')
                && (! $cliente || ($request->user()->operaEnSucursal($cliente->sucursal_id) && Gate::allows('update', $cliente))),
        ]);
    }

    public function detalle(SicQuery $consulta, \App\Services\SICs\SicOperacionService $service)
    {
        Gate::authorize('read historial-crediticio');
        Gate::authorize('view', $consulta->cliente);
        app(\App\Services\ActivityLogService::class)->log(\App\Enums\ActivityEvent::SicEvidenceViewed, 'Resultado SIC visualizado', $consulta->cliente,
            ['provider' => $consulta->procedencia ?? ($consulta->ambiente === 'qa' && $consulta->estado_operacion ? 'cdc_sandbox' : 'desconocida'), 'related' => ['type' => 'sic_query', 'id' => $consulta->id]], request()->user(),
            teamId: request()->user()->current_team_id, sucursalId: $consulta->sucursal_id, inheritActorContext: false);
        $resultado = $service->leer($consulta, 'resultado');
        if (! $resultado) {
            $resultado = app(\App\Services\SICs\SicResultado::class)->historico($consulta);
        }

        $evolucion = [];
        $modelo = $resultado['scores'][0]['modelo'] ?? null;
        if (! $consulta->escenario_clave && $consulta->ambiente === 'qa' && $modelo && ! in_array($modelo, ['Modelo no informado', 'Valor heredado sin modelo acreditado'], true)) {
            foreach (SicQuery::where('cliente_id', $consulta->cliente_id)->where('sic_api_id', $consulta->sic_api_id)
                ->whereNull('escenario_clave')->where('ambiente', 'qa')->where('estado_operacion', 'completada')->orderByDesc('fecha_consulta')->orderByDesc('id')->limit(20)->get()->reverse() as $q) {
                foreach (($service->leer($q, 'resultado')['scores'] ?? []) as $s) {
                    if ($s['modelo'] === $modelo) {
                        $evolucion[] = ['etiqueta' => $q->fecha_consulta->format('d/m/Y').' #'.$q->id, 'score' => $s['valor']];
                        break;
                    }
                }
            }
        }

        return Inertia::render('HistorialCrediticio/Show', [
            'resumen' => $resultado ? app(\App\Services\SICs\SicResumen::class)->construir($resultado) : null,
            'puedeConciliar' => $consulta->ambiente === 'qa' && $consulta->estado_operacion === 'incierta'
                && Gate::allows('reconcile circulo-credito') && Gate::allows('create circulo-credito') && app(\App\Services\SICs\SicOperacionService::class)->puedeConciliar($consulta, request()->user()),
            'evolucion' => $evolucion,
            'consulta' => [...$consulta->only(['id', 'fecha_consulta', 'status', 'ambiente', 'estado_operacion', 'http_status', 'solicitud_revision_id']),
                'cliente' => $consulta->cliente->only(['id', 'primer_nombre', 'apellido_paterno']), 'servicio' => $consulta->api?->nombre],
            'escenarioQa' => $consulta->escenario_clave ? array_intersect_key($service->leer($consulta, 'escenario') ?? [], array_flip(['clave', 'nombre', 'version', 'procedencia'])) : null,
            'resultado' => $resultado,
            'eventos' => \App\Models\SicEvento::where('sic_query_id', $consulta->id)->with('actor:id,name')->orderBy('id')
                ->get(['id', 'estado', 'actor_id', 'created_at'])->map(fn ($evento) => [
                    ...$evento->only(['id', 'estado', 'created_at']),
                    'actor' => $evento->actor?->only(['name']),
                    'automatico' => in_array($evento->estado, ['enviando', 'completada', 'error', 'cancelada', 'incierta', 'respuesta_tardia'], true),
                ]),
        ])->toResponse(request())->header('Cache-Control', 'private, no-store');
    }

    public function conciliar(Request $request, SicQuery $consulta, \App\Services\SICs\SicOperacionService $service)
    {
        Gate::authorize('reconcile circulo-credito');
        Gate::authorize('view', $consulta->cliente);
        $data = $request->validate(['motivo' => ['required', 'string', 'min:20', 'max:1000'],
            'referencia' => ['required', 'string', 'max:100'], 'confirmacion_revision' => ['accepted']],
            ['confirmacion_revision.accepted' => 'Confirma que revisaste el intento con el proveedor y que no hay un resultado acreditable.'],
            ['motivo' => 'motivo de conciliación', 'referencia' => 'referencia de revisión con el proveedor']);
        $service->conciliar($consulta, $data, $request->user());

        return redirect()->route('consultas-sic.show', $consulta)->with('success', 'Intento conciliado sin resultado acreditable. Se conserva toda su historia.');
    }
}
