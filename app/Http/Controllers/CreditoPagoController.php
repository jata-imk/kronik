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

    public function anticipo(Credito $credito, PagoService $service)
    {
        abort_unless(Gate::allows('prepay', $credito) || Gate::allows('settle', $credito), 403);
        $ops = [];
        if (Gate::allows('prepay', $credito)) {
            $ops[] = ['value' => 'prepago', 'label' => 'Anticipo parcial'];
        }
        if (Gate::allows('settle', $credito)) {
            $ops[] = ['value' => 'liquidacion', 'label' => 'Liquidación total'];
        }

        return Inertia::render('Creditos/Anticipo', ['credito' => $credito->load(['cliente:id,primer_nombre,apellido_paterno', 'sucursal:id,nombre']),
            'situacion' => $service->consultar($credito), 'hoy' => app(FechaEmpresa::class)->hoy()->toDateString(), 'operaciones' => $ops,
            'opcionales' => collect($credito->condiciones['producto']['comisiones'])->map(fn ($c, $i) => [...$c, 'indice' => $i])->filter(fn ($c) => ! $c['obligatoria'] && in_array($c['momento_cobro'], ['evento', 'liquidacion'], true))->values(),
            'habilitado' => config('originacion.pagos_qa_habilitados') === true && config('originacion.anticipos_qa_habilitados') === true]);
    }

    public function store(RegistrarPagoRequest $request, Credito $credito, PagoService $service)
    {
        $pago = $service->registrar($credito, $request->validated(), $request->user());

        return redirect()->route('creditos.pagos.show', [$credito, $pago])->with('success', 'Operación QA registrada. No se ejecutó ningún cobro bancario.');
    }

    public function show(Credito $credito, CreditoPago $pago)
    {
        Gate::authorize('view', $credito);
        abort_unless($pago->credito_id === $credito->id, 404);
        if (! hash_equals($pago->snapshot_hash, hash('sha256', json_encode($pago->snapshot, JSON_THROW_ON_ERROR)))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['pago' => 'La evidencia del recibo cambió. Solicita revisión al administrador.']);
        }

        return Inertia::render('Creditos/Recibo', ['credito' => $credito->only(['id', 'solicitud_id']),
            'pago' => [...$pago->only(['id', 'tipo', 'importe', 'fecha_efectiva', 'created_at', 'actor_id', 'reversa_de']),
                'referencia' => $pago->referencia, 'motivo' => $pago->motivo, 'asignaciones' => $pago->snapshot['asignaciones'], 'reverso_id' => $pago->reverso?->id,
                'cronograma_version' => $pago->snapshot['despues']['cronograma_version'] ?? 1,
                'estado_operativo' => app(\App\Services\Credito\MotorPagos::class)->resumen($pago->snapshot['despues'])['estado_operativo']],
            'puedeReversar' => Gate::allows('reversePayment', $credito) && in_array($pago->tipo, ['pago', 'prepago', 'liquidacion'], true) && ! $pago->reverso
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
