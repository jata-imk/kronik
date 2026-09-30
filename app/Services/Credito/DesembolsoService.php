<?php

namespace App\Services\Credito;

use App\Enums\ActivityEvent;
use App\Enums\SolicitudEstado;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\CreditoDesembolso;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Models\SolicitudFirma;
use App\Models\SolicitudPaquete;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\FechaEmpresa;
use App\Services\SolicitudFirmaService;
use App\Services\SolicitudRequisitosService;
use App\Support\Decimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DesembolsoService
{
    public function contratoActual(Solicitud $solicitud): ?SolicitudPaquete
    {
        return SolicitudPaquete::where('solicitud_id', $solicitud->id)
            ->where('solicitud_resolucion_id', $solicitud->resoluciones()->latest('id')->value('id'))
            ->with(['formalizacion', 'documento'])->first();
    }

    public function requisitos(Solicitud $solicitud, User $actor): array
    {
        $items = app(SolicitudRequisitosService::class)->evaluar($solicitud, $actor, true, 'disburse', SolicitudEstado::Formalizada)['requisitos'];
        foreach ($items as &$item) {
            if ($item['clave'] === 'estado') {
                $item['mensaje'] = 'Primero acepta la copia firmada de la aprobación actual en Contrato y tabla de pagos.';
            }
        }
        unset($item);
        $paquete = $this->contratoActual($solicitud);
        $formalizacion = $paquete?->formalizacion;
        $tabla = $paquete?->snapshot['tabla'] ?? [];
        $items[] = ['clave' => 'qa_desembolso', 'cumplido' => config('originacion.desembolsos_qa_habilitados') === true,
            'mensaje' => 'El administrador técnico debe habilitar ORIGINACION_DESEMBOLSOS_QA_HABILITADOS y reconstruir la caché en QA. No autoriza dinero real.'];
        $items[] = ['clave' => 'sucursal', 'cumplido' => (bool) $actor->currentSucursal?->activa && (int) $actor->current_sucursal_id === (int) $solicitud->sucursal_id,
            'mensaje' => 'Selecciona la sucursal activa responsable antes de registrar el desembolso.'];
        $items[] = ['clave' => 'formalizacion', 'cumplido' => $formalizacion?->modo === 'qa' && ($paquete?->snapshot['formato'] ?? null) === 2,
            'mensaje' => 'Falta una formalización QA vigente con tabla fiscal conservada. No se convierten contratos históricos.'];
        $items[] = ['clave' => 'fecha_desembolso', 'cumplido' => ($tabla['escenario']['fecha_disposicion'] ?? null) === app(FechaEmpresa::class)->hoy()->toDateString(),
            'mensaje' => 'El registro debe hacerse hoy y coincidir con la fecha firmada. Si es futura, espera; si cambió, devuelve, revisa y formaliza una nueva tabla. No se admiten desembolsos retroactivos.'];
        $items[] = ['clave' => 'unico', 'cumplido' => ! $solicitud->credito()->exists(), 'mensaje' => 'Esta solicitud ya tiene crédito y desembolso. Abre el crédito; no repitas el registro.'];
        if ($formalizacion) {
            try {
                $this->verificarFormalizacion($paquete);
                $items[] = ['clave' => 'integridad', 'cumplido' => true, 'mensaje' => 'Original y copia firmada íntegros.'];
            } catch (ValidationException $exception) {
                $items[] = ['clave' => 'integridad', 'cumplido' => false, 'mensaje' => collect($exception->errors())->flatten()->implode(' ')];
            }
        }

        return ['requisitos' => $items, 'permitido' => ! collect($items)->contains('cumplido', false),
            'resumen' => $tabla ? ['fecha' => $tabla['escenario']['fecha_disposicion'],
                'monto' => $tabla['escenario']['monto_solicitado'], 'capital' => $tabla['escenario']['saldo_financiado'],
                'importe' => $tabla['escenario']['efectivo_entregado'], 'retenido' => $tabla['totales']['retenido_desembolso'],
                'financiado' => $tabla['totales']['financiado_comisiones'], 'separado' => $tabla['totales']['pago_separado_inicial']] : null];
    }

    public function registrar(Solicitud $solicitud, array $data, User $actor): Credito
    {
        try {
            return DB::transaction(function () use ($solicitud, $data, $actor) {
                $solicitud = Solicitud::lockForUpdate()->findOrFail($solicitud->id);
                Gate::forUser($actor)->authorize('disburse', $solicitud);
                if (config('originacion.desembolsos_qa_habilitados') !== true || ! $actor->currentSucursal?->activa
                    || (int) $actor->current_sucursal_id !== (int) $solicitud->sucursal_id) {
                    $this->error('Habilita desembolsos QA y selecciona la sucursal activa responsable.');
                }
                $payload = ['solicitud_id' => $solicitud->id, 'actor_id' => $actor->id, 'fecha' => $data['fecha_desembolso'],
                    'importe' => Decimal::round((string) $data['importe']), 'referencia' => trim($data['referencia'])];
                $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
                $existing = CreditoDesembolso::where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    if (! hash_equals($existing->payload_hash, $hash)) {
                        $this->error('Este envío ya se registró con otros datos. Actualiza la página.');
                    }

                    return Credito::findOrFail($existing->credito_id);
                }
                if ($solicitud->credito()->exists()) {
                    $this->error('Esta solicitud ya tiene un desembolso. Abre el crédito existente.');
                }
                Cliente::lockForUpdate()->findOrFail($solicitud->cliente_id);
                if ($solicitud->producto_version_id) {
                    ProductoVersion::lockForUpdate()->findOrFail($solicitud->producto_version_id);
                }
                if ($solicitud->lock_version !== (int) $data['lock_version']) {
                    $this->error('La solicitud cambió. Actualiza la página antes de registrar el desembolso.');
                }
                $check = $this->requisitos($solicitud, $actor);
                if (! $check['permitido']) {
                    $this->error(collect($check['requisitos'])->where('cumplido', false)->pluck('mensaje')->implode(' '));
                }
                $resumen = $check['resumen'];
                if ($data['fecha_desembolso'] !== $resumen['fecha'] || $data['fecha_desembolso'] !== app(FechaEmpresa::class)->hoy()->toDateString()) {
                    $this->error('La fecha debe ser hoy y coincidir con la tabla firmada. Si cambió, requiere nueva formalización.');
                }
                if (Decimal::compare($payload['importe'], $resumen['importe']) !== 0) {
                    $this->error('El importe transferido debe coincidir exactamente con el efectivo a entregar de la tabla firmada.');
                }
                $referenciaHash = hash('sha256', mb_strtoupper($payload['referencia'], 'UTF-8'));
                if (CreditoDesembolso::where('referencia_hash', $referenciaHash)->exists()) {
                    $this->error('Esta referencia de transferencia ya se registró. Revisa el crédito original; no cambies la referencia para duplicarla.');
                }
                $paquete = $this->contratoActual($solicitud);
                $snapshot = $paquete->snapshot;
                $condiciones = ['modo' => 'qa', 'paquete_hash' => $paquete->snapshot_hash, 'formalizacion_hash' => $paquete->formalizacion->snapshot_hash,
                    'condiciones' => $snapshot['condiciones'], 'producto' => $snapshot['producto']];
                $credito = Credito::create(['solicitud_id' => $solicitud->id, 'solicitud_formalizacion_id' => $paquete->formalizacion->id,
                    'cliente_id' => $solicitud->cliente_id, 'sucursal_id' => $solicitud->sucursal_id, 'responsable_id' => $solicitud->responsable_id,
                    'producto_version_id' => $solicitud->producto_version_id, 'fecha_desembolso' => $resumen['fecha'], 'capital_inicial' => $resumen['capital'],
                    'estado' => 'activo', 'modo' => 'qa', 'condiciones' => $condiciones,
                    'condiciones_hash' => hash('sha256', json_encode($condiciones, JSON_THROW_ON_ERROR))]);
                $credito->cronogramas()->create(['version' => 1, 'snapshot' => $snapshot['tabla'],
                    'snapshot_hash' => hash('sha256', json_encode($snapshot['tabla'], JSON_THROW_ON_ERROR))]);
                $desembolso = $credito->desembolso()->create(['registrado_por' => $actor->id, 'idempotency_key' => $data['idempotency_key'], 'payload_hash' => $hash,
                    'fecha_efectiva' => $resumen['fecha'], 'importe' => $payload['importe'], 'referencia' => $payload['referencia'], 'referencia_hash' => $referenciaHash, 'medio' => 'transferencia']);
                $credito->movimientos()->create(['credito_desembolso_id' => $desembolso->id, 'actor_id' => $actor->id,
                    'tipo' => 'capital_inicial', 'fecha_efectiva' => $resumen['fecha'], 'importe' => $resumen['capital']]);
                $solicitud->estado = SolicitudEstado::Desembolsada;
                $solicitud->lock_version++;
                $solicitud->save();
                $solicitud->eventos()->create(['actor_id' => $actor->id, 'tipo' => 'desembolso_qa', 'datos' => ['credito_id' => $credito->id, 'desembolso_id' => $desembolso->id]]);
                app(ActivityLogService::class)->log(ActivityEvent::CreditDisbursed, 'Desembolso manual QA registrado', $solicitud,
                    ['related' => ['type' => 'credito', 'id' => $credito->id]], $actor, sucursalId: $solicitud->sucursal_id);

                return $credito;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $this->error('El desembolso o su referencia ya fue registrado por otra operación. Actualiza la página y revisa el crédito; no repitas la transferencia.');
        }
    }

    private function verificarFormalizacion(SolicitudPaquete $paquete): void
    {
        $formalizacion = $paquete->formalizacion;
        $firma = SolicitudFirma::find($formalizacion->solicitud_firma_id);
        $snapshot = $formalizacion->snapshot;
        if (! hash_equals($formalizacion->snapshot_hash, hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)))
            || ! $firma || $firma->estado !== 'aceptada' || $firma->solicitud_paquete_id !== $paquete->id
            || ($snapshot['paquete_hash'] ?? null) !== $paquete->snapshot_hash || ($snapshot['firma_hash'] ?? null) !== $firma->archivo_hash
            || ($snapshot['original_hash'] ?? null) !== $paquete->documento?->archivo_hash
            || ($snapshot['resolucion_id'] ?? null) !== $paquete->solicitud_resolucion_id
            || ($snapshot['revision_id'] ?? null) !== $paquete->solicitud_revision_id) {
            $this->error('La formalización no coincide con la evidencia conservada. Solicita revisión al administrador.');
        }
        app(SolicitudFirmaService::class)->originalIntacto($paquete);
        app(SolicitudFirmaService::class)->verificarArchivo($firma);
    }

    private function error(string $message): never
    {
        throw ValidationException::withMessages(['desembolso' => $message]);
    }
}
