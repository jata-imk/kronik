<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarPagoRequest;
use App\Models\Credito;
use App\Models\CreditoPago;
use App\Services\Credito\PagoService;
use App\Services\FechaEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CreditoPagoController extends Controller
{
    public function create(Credito $credito, PagoService $service)
    {
        Gate::authorize('pay', $credito);

        return Inertia::render('Creditos/Pago', ['credito' => $credito->load(['cliente:id,primer_nombre,apellido_paterno', 'sucursal:id,nombre']),
            'situacion' => $service->consultar($credito), 'hoy' => app(FechaEmpresa::class)->hoy()->toDateString(),
            'habilitado' => config('originacion.pagos_qa_habilitados') === true]);
    }

    public function preview(RegistrarPagoRequest $request, Credito $credito, PagoService $service)
    {
        $previa = $service->previa($credito, $request->validated(), $request->user());
        unset($previa['snapshot']);

        return response()->json($previa)->header('Cache-Control', 'private, no-store');
    }

    public function store(RegistrarPagoRequest $request, Credito $credito, PagoService $service)
    {
        $pago = $service->registrar($credito, $request->validated(), $request->user());

        return redirect()->route('creditos.pagos.show', [$credito, $pago])->with('success', 'Pago QA registrado. No se ejecutó ningún cobro bancario.');
    }

    public function show(Credito $credito, CreditoPago $pago)
    {
        Gate::authorize('view', $credito);
        abort_unless($pago->credito_id === $credito->id, 404);

        return Inertia::render('Creditos/Recibo', ['credito' => $credito->only(['id', 'solicitud_id']),
            'pago' => [...$pago->only(['id', 'tipo', 'importe', 'fecha_efectiva', 'created_at', 'actor_id', 'reversa_de']),
                'referencia' => $pago->referencia, 'motivo' => $pago->motivo, 'asignaciones' => $pago->snapshot['asignaciones'], 'reverso_id' => $pago->reverso?->id],
            'puedeReversar' => Gate::allows('reversePayment', $credito) && $pago->tipo === 'pago' && ! $pago->reverso
                && $credito->pagos()->latest('id')->value('id') === $pago->id]);
    }

    public function reverse(Request $request, Credito $credito, CreditoPago $pago, PagoService $service)
    {
        Gate::authorize('reversePayment', $credito);
        $data = $request->validate(['motivo' => ['required', 'string', 'min:10', 'max:1000'], 'idempotency_key' => ['required', 'uuid'],
            'confirmacion_qa' => ['accepted']], ['confirmacion_qa.accepted' => 'Confirma el reverso compensatorio de QA.'], ['motivo' => 'motivo del reverso']);
        $reverso = $service->reversar($credito, $pago, $data, $request->user());

        return redirect()->route('creditos.pagos.show', [$credito, $reverso])->with('success', 'Reverso registrado; se conserva el recibo original.');
    }
}
