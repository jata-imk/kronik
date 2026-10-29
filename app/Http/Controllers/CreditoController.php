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
        $creditos = Credito::with(['cliente:id,primer_nombre,apellido_paterno,apellido_materno', 'sucursal:id,nombre', 'responsable:id,name', 'ultimoPago'])
            ->when($filters['sucursal_id'] ?? null, fn ($q, $id) => $q->where('sucursal_id', $id))
            ->when($filters['buscar'] ?? null, fn ($q, $buscar) => $q->where(fn ($q) => $q
                ->where('folio', 'like', '%'.$buscar.'%')
                ->orWhere('id', ctype_digit($buscar) ? $buscar : 0)
                ->orWhereHas('cliente', fn ($c) => $c->where('primer_nombre', 'like', '%'.$buscar.'%')->orWhere('apellido_paterno', 'like', '%'.$buscar.'%'))))
            ->orderBy('id', ($filters['orden'] ?? 'recientes') === 'antiguos' ? 'asc' : 'desc')->paginate($filters['por_pagina'] ?? 25)->withQueryString();

        $creditos->getCollection()->each(function ($c) {
            $c->estado_operativo = 'activo';
            if ($p = $c->ultimoPago) {
                try {
                    $ok = hash_equals($p->snapshot_hash, hash('sha256', json_encode($p->snapshot, JSON_THROW_ON_ERROR)));
                    $c->estado_operativo = $ok ? app(\App\Services\Credito\MotorPagos::class)->resumen($p->snapshot['despues'])['estado_operativo'] : 'por_revisar';
                } catch (\Throwable) {
                    $c->estado_operativo = 'por_revisar';
                }
            }
            $c->unsetRelation('ultimoPago');
        });

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
        $situacion = $pagos->consultar($credito);
        $data = request()->validate(['calendario' => ['nullable', 'integer', 'min:1']]);
        $version = $data['calendario'] ?? ($situacion['resumen']['cronograma_version'] ?? 1);
        $cronograma = $credito->cronogramas()->where('version', $version)->firstOrFail();
        if (! hash_equals($cronograma->snapshot_hash, hash('sha256', json_encode($cronograma->snapshot, JSON_THROW_ON_ERROR)))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['calendario' => 'La evidencia del calendario cambió. Solicita revisión al administrador.']);
        }

        return Inertia::render('Creditos/Show', [
            'credito' => $credito,
            'desembolso' => [...$desembolso->only(['id', 'importe', 'fecha_efectiva', 'created_at', 'registrado_por', 'medio']), 'referencia' => $desembolso->referencia],
            'cronograma' => ['version' => $cronograma->version, 'snapshot_hash' => $cronograma->snapshot_hash, 'tabla' => $cronograma->snapshot],
            'cronogramas' => $credito->cronogramas()->orderByDesc('version')->get(['id', 'version', 'fecha_efectiva', 'credito_pago_id']),
            'movimientos' => $credito->movimientos()->latest('id')->paginate(25),
            'pagos' => $credito->pagos()->latest('id')->paginate(10, ['id', 'folio', 'credito_id', 'tipo', 'importe', 'fecha_efectiva', 'created_at', 'reversa_de'], 'pagos_page'),
            'situacion' => $situacion,
            'puedePagar' => Gate::allows('pay', $credito) && ($situacion['resumen']['estado_operativo'] ?? null) === 'activo',
            'puedeAnticipar' => (Gate::allows('prepay', $credito) || Gate::allows('settle', $credito)) && ($situacion['resumen']['estado_operativo'] ?? null) === 'activo',
            'puedeVerContrato' => Gate::allows('viewPackage', $credito->solicitud),
        ]);
    }
}
