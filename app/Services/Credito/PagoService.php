<?php

namespace App\Services\Credito;

use App\Enums\ActivityEvent;
use App\Models\Credito;
use App\Models\CreditoPago;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\FechaEmpresa;
use App\Support\Decimal as D;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class PagoService
{
    public function __construct(private MotorPagos $motor, private ImpuestoConceptoService $impuestos, private FechaEmpresa $fechaEmpresa) {}

    public function consultar(Credito $credito): array
    {
        try {
            $state = $this->estado($credito);

            return ['resumen' => $this->motor->resumen($this->motor->avanzar($state, $this->fechaEmpresa->hoy()->toDateString())), 'error' => null];
        } catch (ValidationException $e) {
            return ['resumen' => null, 'error' => collect($e->errors())->flatten()->implode(' ')];
        }
    }

    public function previa(Credito $credito, array $data, User $actor): array
    {
        $this->autorizar($credito, $actor, 'pay');
        $this->validarFecha($credito, $data['fecha_pago']);
        $antes = $this->estado($credito);
        $alDia = $this->motor->avanzar($antes, $data['fecha_pago']);
        $resultado = $this->motor->aplicar($alDia, (string) $data['importe']);
        foreach ($resultado['estado']['cargos'] as &$cargo) {
            if ($cargo['tipo'] !== 'comision' || $cargo['fecha'] <= $data['fecha_pago']) {
                $cargo['contabilizado'] = D::round($cargo['generado']);
                $cargo['impuesto_contabilizado'] = $cargo['fiscalidad'] === null ? '0.00'
                    : $this->impuestos->calcular($cargo['fiscalidad'], $cargo['contabilizado'], $cargo['concepto'])['impuesto'];
            }
        }
        unset($cargo);
        $payload = $this->payload($credito, $data, $actor);

        return ['asignaciones' => $resultado['asignaciones'], 'antes' => $this->motor->resumen($alDia), 'despues' => $resultado['resumen'],
            'previa_hash' => $this->hash([$payload, $antes, $resultado]),
            'snapshot' => ['antes' => $antes, 'despues' => $resultado['estado'], 'asignaciones' => $resultado['asignaciones']]];
    }

    public function registrar(Credito $credito, array $data, User $actor): CreditoPago
    {
        try {
            return DB::transaction(function () use ($credito, $data, $actor) {
                $credito = Credito::lockForUpdate()->findOrFail($credito->id);
                $this->autorizar($credito, $actor, 'pay');
                $payload = $this->payload($credito, $data, $actor);
                if ($existing = $this->reintento($data['idempotency_key'], $this->hash($payload))) {
                    return $existing;
                }
                $previa = $this->previa($credito, $data, $actor);
                if (! hash_equals($previa['previa_hash'], $data['previa_hash'])) {
                    $this->error('El crédito o la captura cambiaron. Vuelve a revisar la distribución antes de confirmar.');
                }
                $referenciaHash = hash('sha256', mb_strtoupper(trim($data['referencia']), 'UTF-8'));
                if (CreditoPago::where('referencia_hash', $referenciaHash)->exists()) {
                    $this->error('La referencia del pago ya está registrada. Revisa el recibo original; no cambies la referencia para duplicarlo.');
                }
                $pago = $credito->pagos()->create(['tipo' => 'pago', 'actor_id' => $actor->id, 'fecha_efectiva' => $data['fecha_pago'],
                    'importe' => D::round((string) $data['importe']), 'referencia' => trim($data['referencia']), 'referencia_hash' => $referenciaHash,
                    'idempotency_key' => $data['idempotency_key'], 'payload_hash' => $this->hash($payload),
                    'snapshot' => $previa['snapshot'], 'snapshot_hash' => $this->hash($previa['snapshot'])]);
                $this->movimientos($credito, $pago, $previa['snapshot']);
                app(ActivityLogService::class)->log(ActivityEvent::CreditPaymentRecorded, 'Pago manual QA registrado', $credito,
                    ['related' => ['type' => 'credito_pago', 'id' => $pago->id]], $actor, sucursalId: $credito->sucursal_id);

                return $pago;
            });
        } catch (UniqueConstraintViolationException $e) {
            $this->error('Otra operación ya registró este pago o referencia. Actualiza la página y revisa el historial.');
        }
    }

    public function reversar(Credito $credito, CreditoPago $pago, array $data, User $actor): CreditoPago
    {
        try {
            return DB::transaction(function () use ($credito, $pago, $data, $actor) {
                $credito = Credito::lockForUpdate()->findOrFail($credito->id);
                $this->autorizar($credito, $actor, 'reversePayment');
                abort_unless($pago->credito_id === $credito->id, 404);
                $hash = $this->hash(['tipo' => 'reverso', 'credito' => $credito->id, 'pago' => $pago->id, 'actor' => $actor->id, 'motivo' => $data['motivo']]);
                if ($existing = $this->reintento($data['idempotency_key'], $hash)) {
                    return $existing;
                }
                if ($pago->tipo !== 'pago' || $pago->reverso()->exists() || $credito->pagos()->latest('id')->value('id') !== $pago->id
                    || $credito->movimientos()->whereDate('fecha_efectiva', '>', $pago->fecha_efectiva->toDateString())->exists()) {
                    $this->error('En esta versión solo puedes revertir el último pago, sin movimientos posteriores. No se borra ni recalcula historia posterior.');
                }
                $this->estado($credito);
                $this->verificar($pago->snapshot, $pago->snapshot_hash);
                $snapshot = ['antes' => $pago->snapshot['despues'], 'despues' => $pago->snapshot['antes'], 'asignaciones' => $pago->snapshot['asignaciones']];
                $reverso = $credito->pagos()->create(['tipo' => 'reverso', 'reversa_de' => $pago->id, 'actor_id' => $actor->id,
                    'fecha_efectiva' => $pago->fecha_efectiva, 'importe' => $pago->importe, 'motivo' => $data['motivo'],
                    'idempotency_key' => $data['idempotency_key'], 'payload_hash' => $hash, 'snapshot' => $snapshot, 'snapshot_hash' => $this->hash($snapshot)]);
                foreach ($credito->movimientos()->where('credito_pago_id', $pago->id)->get() as $movimiento) {
                    $credito->movimientos()->create(['credito_pago_id' => $reverso->id, 'actor_id' => $actor->id, 'tipo' => 'reverso',
                        'concepto' => $movimiento->concepto, 'fecha_efectiva' => $pago->fecha_efectiva, 'importe' => D::sub('0', $movimiento->importe, 2)]);
                }
                app(ActivityLogService::class)->log(ActivityEvent::CreditPaymentReversed, 'Pago QA revertido por compensación', $credito,
                    ['related' => ['type' => 'credito_pago', 'id' => $reverso->id]], $actor, sucursalId: $credito->sucursal_id);

                return $reverso;
            });
        } catch (UniqueConstraintViolationException $e) {
            $this->error('Otra operación ya registró esta corrección. Actualiza la página y revisa el historial antes de continuar.');
        }
    }

    private function estado(Credito $credito): array
    {
        $this->verificar($credito->condiciones, $credito->condiciones_hash);
        $cronograma = $credito->cronogramas()->where('version', 1)->firstOrFail();
        $this->verificar($cronograma->snapshot, $cronograma->snapshot_hash);
        if ($last = $credito->pagos()->latest('id')->first()) {
            $this->verificar($last->snapshot, $last->snapshot_hash);

            return $last->snapshot['despues'];
        }

        return $this->motor->iniciar($credito->condiciones['producto'], $cronograma->snapshot);
    }

    private function autorizar(Credito $credito, User $actor, string $accion): void
    {
        Gate::forUser($actor)->authorize($accion, $credito);
        // Super Admin bypasses policies, but not these operational invariants.
        if (! $actor->currentSucursal?->activa || (int) $actor->current_sucursal_id !== (int) $credito->sucursal_id) {
            $this->error('Selecciona la sucursal activa responsable del crédito.');
        }
        if ($credito->modo !== 'qa' || config('originacion.pagos_qa_habilitados') !== true) {
            $this->error('El administrador técnico debe habilitar ORIGINACION_PAGOS_QA_HABILITADOS y reconstruir la caché de configuración exclusivamente en QA. No autoriza dinero real.');
        }
    }

    private function validarFecha(Credito $credito, string $fecha): void
    {
        if ($fecha < $credito->fecha_desembolso->toDateString() || $fecha > $this->fechaEmpresa->hoy()->toDateString()) {
            $this->error('La fecha debe estar entre el desembolso y hoy, en la zona horaria de la institución. No se admiten pagos futuros.');
        }
        if ($credito->movimientos()->whereDate('fecha_efectiva', '>', $fecha)->exists()) {
            $this->error('No puedes registrar este pago con esa fecha porque el crédito tiene movimientos posteriores.');
        }
    }

    private function payload(Credito $credito, array $data, User $actor): array
    {
        return ['tipo' => 'pago', 'credito' => $credito->id, 'actor' => $actor->id, 'fecha' => $data['fecha_pago'],
            'importe' => D::round((string) $data['importe']), 'referencia' => trim($data['referencia'])];
    }

    private function reintento(string $key, string $hash): ?CreditoPago
    {
        $existing = CreditoPago::where('idempotency_key', $key)->first();
        if ($existing && ! hash_equals($existing->payload_hash, $hash)) {
            $this->error('Este envío ya fue utilizado con otros datos. Revisa el registro original.');
        }

        return $existing;
    }

    private function movimientos(Credito $credito, CreditoPago $pago, array $snapshot): void
    {
        foreach ($snapshot['despues']['cargos'] as $key => $cargo) {
            $anterior = $snapshot['antes']['cargos'][$key];
            foreach (['devengo' => D::sub($cargo['contabilizado'], $anterior['contabilizado'], 2), 'impuesto' => D::sub($cargo['impuesto_contabilizado'], $anterior['impuesto_contabilizado'], 2),
                'pago_concepto' => D::sub($anterior['pagado'], $cargo['pagado'], 2), 'pago_impuesto' => D::sub($anterior['impuesto_pagado'], $cargo['impuesto_pagado'], 2)] as $tipo => $importe) {
                if (D::compare($importe, '0') !== 0) {
                    $credito->movimientos()->create(['credito_pago_id' => $pago->id, 'actor_id' => $pago->actor_id, 'tipo' => $tipo,
                        'concepto' => $key, 'fecha_efectiva' => $pago->fecha_efectiva, 'importe' => $importe]);
                }
            }
        }
    }

    private function hash(array $data): string
    {
        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function verificar(array $data, string $hash): void
    {
        if (! hash_equals($hash, $this->hash($data))) {
            $this->error('La evidencia del crédito cambió. Solicita revisión al administrador antes de registrar pagos.');
        }
    }

    private function error(string $message): never
    {
        throw ValidationException::withMessages(['pago' => $message]);
    }
}
