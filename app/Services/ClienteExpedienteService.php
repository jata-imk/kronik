<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ClienteDocumentoTipo;
use App\Models\Cliente;
use App\Models\ClienteGarantia;
use App\Models\ClienteReferencia;
use App\Models\ClienteVinculo;
use App\Models\DocumentoGenerado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClienteExpedienteService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function initializeChecklist(Cliente $cliente): void
    {
        foreach (ClienteDocumentoTipo::requeridos() as $tipo) {
            $cliente->documentos()->firstOrCreate(
                ['tipo' => $tipo->value, 'version' => 1],
                ['estado' => 'pendiente', 'es_actual' => true],
            );
        }
    }

    public function updateProfile(Cliente $cliente, array $data): Cliente
    {
        return DB::transaction(function () use ($cliente, $data) {
            $cliente = Cliente::whereKey($cliente->id)->lockForUpdate()->firstOrFail();
            $cliente->fill($data);
            if (! $cliente->isDirty()) {
                return $cliente;
            }
            $cliente->save();

            $this->log(
                $cliente,
                ActivityEvent::ClientKycUpdated,
                'Perfil KYC actualizado',
                ['changed_fields' => $this->activityLog->fieldNames($data)],
            );

            return $cliente->refresh();
        });
    }

    public function storeReferencia(Cliente $cliente, array $data): ClienteReferencia
    {
        return DB::transaction(function () use ($cliente, $data) {
            $referencia = $cliente->referencias()->create($data);
            $this->log($cliente, ActivityEvent::ClientReferenceCreated, 'Referencia de cliente creada', [
                'related' => ['type' => 'cliente_referencia', 'id' => $referencia->id],
            ]);

            return $referencia;
        });
    }

    public function updateReferencia(ClienteReferencia $referencia, array $data): ClienteReferencia
    {
        return DB::transaction(function () use ($referencia, $data) {
            $referencia = ClienteReferencia::whereKey($referencia->id)->lockForUpdate()->firstOrFail();
            $referencia->fill($data);
            if (! $referencia->isDirty()) {
                return $referencia;
            }
            $referencia->save();
            $this->log($referencia->cliente, ActivityEvent::ClientReferenceUpdated, 'Referencia de cliente actualizada', [
                'changed_fields' => $this->activityLog->fieldNames($data),
                'related' => ['type' => 'cliente_referencia', 'id' => $referencia->id],
            ]);

            return $referencia->refresh();
        });
    }

    public function deleteReferencia(ClienteReferencia $referencia): void
    {
        DB::transaction(function () use ($referencia) {
            $cliente = $referencia->cliente;
            $id = $referencia->id;
            $referencia->delete();
            $this->log($cliente, ActivityEvent::ClientReferenceDeleted, 'Referencia de cliente eliminada', [
                'related' => ['type' => 'cliente_referencia', 'id' => $id],
            ]);
        });
    }

    public function storeVinculo(Cliente $cliente, array $data): ClienteVinculo
    {
        return DB::transaction(function () use ($cliente, $data) {
            $vinculo = $cliente->vinculos()->create($data);
            $this->log($cliente, ActivityEvent::ClientLinkCreated, 'Persona vinculada al expediente', [
                'related' => ['type' => 'cliente_vinculo', 'id' => $vinculo->id],
            ]);

            return $vinculo;
        });
    }

    public function deleteVinculo(ClienteVinculo $vinculo): void
    {
        DB::transaction(function () use ($vinculo) {
            $cliente = $vinculo->cliente;
            $vinculado = $vinculo->vinculado;

            $usadoComoPropietario = ClienteGarantia::query()
                ->where(fn ($query) => $query
                    ->where('cliente_id', $cliente->id)
                    ->where('propietario_cliente_id', $vinculado->id))
                ->orWhere(fn ($query) => $query
                    ->where('cliente_id', $vinculado->id)
                    ->where('propietario_cliente_id', $cliente->id))
                ->exists();

            if ($usadoComoPropietario) {
                throw ValidationException::withMessages([
                    'cliente_vinculado_id' => 'Reasigne las garantías de esta relación antes de eliminarla.',
                ]);
            }

            $id = $vinculo->id;
            $vinculo->delete();
            $this->log($cliente, ActivityEvent::ClientLinkDeleted, 'Persona desvinculada del expediente', [
                'related' => ['type' => 'cliente_vinculo', 'id' => $id],
            ]);
        });
    }

    public function storeGarantia(Cliente $cliente, array $data): ClienteGarantia
    {
        return DB::transaction(function () use ($cliente, $data) {
            $garantia = $cliente->garantias()->create($data);
            $this->log($cliente, ActivityEvent::ClientGuaranteeCreated, 'Garantia creada', [
                'related' => ['type' => 'cliente_garantia', 'id' => $garantia->id],
            ]);

            return $garantia;
        });
    }

    public function updateGarantia(ClienteGarantia $garantia, array $data): ClienteGarantia
    {
        return DB::transaction(function () use ($garantia, $data) {
            $garantia = ClienteGarantia::whereKey($garantia->id)->lockForUpdate()->firstOrFail();
            $garantia->fill($data);
            if (! $garantia->isDirty()) {
                return $garantia;
            }
            $garantia->save();
            $this->log($garantia->cliente, ActivityEvent::ClientGuaranteeUpdated, 'Garantia actualizada', [
                'changed_fields' => $this->activityLog->fieldNames($data),
                'related' => ['type' => 'cliente_garantia', 'id' => $garantia->id],
            ]);

            return $garantia->refresh();
        });
    }

    public function deleteGarantia(ClienteGarantia $garantia): void
    {
        DB::transaction(function () use ($garantia) {
            if (DocumentoGenerado::query()->where('documentable_type', 'cliente_garantias')->where('documentable_id', $garantia->id)->exists()) {
                throw ValidationException::withMessages([
                    'garantia' => 'La garantía tiene documentos generados y debe conservarse para mantener su trazabilidad.',
                ]);
            }
            $cliente = $garantia->cliente;
            $id = $garantia->id;
            $garantia->delete();
            $this->log($cliente, ActivityEvent::ClientGuaranteeDeleted, 'Garantia eliminada', [
                'related' => ['type' => 'cliente_garantia', 'id' => $id],
            ]);
        });
    }

    private function log(Cliente $cliente, ActivityEvent $event, string $description, array $metadata): void
    {
        $this->activityLog->log(
            event: $event,
            description: $description,
            subject: $cliente,
            metadata: $metadata,
            causer: Auth::user(),
            teamId: Auth::user()?->current_team_id,
            sucursalId: $cliente->sucursal_id,
            inheritActorContext: false,
        );
    }
}
