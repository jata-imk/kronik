<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ClienteDocumentoTipo;
use App\Models\Cliente;
use App\Models\ClienteGarantia;
use App\Models\ClienteReferencia;
use App\Models\ClienteVinculo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            $cliente->update($data);

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
        $referencia = $cliente->referencias()->create($data);
        $this->log($cliente, ActivityEvent::ClientReferenceCreated, 'Referencia de cliente creada', [
            'related' => ['type' => 'cliente_referencia', 'id' => $referencia->id],
        ]);

        return $referencia;
    }

    public function updateReferencia(ClienteReferencia $referencia, array $data): ClienteReferencia
    {
        $referencia->update($data);
        $this->log($referencia->cliente, ActivityEvent::ClientReferenceUpdated, 'Referencia de cliente actualizada', [
            'changed_fields' => $this->activityLog->fieldNames($data),
            'related' => ['type' => 'cliente_referencia', 'id' => $referencia->id],
        ]);

        return $referencia->refresh();
    }

    public function deleteReferencia(ClienteReferencia $referencia): void
    {
        $cliente = $referencia->cliente;
        $id = $referencia->id;
        $referencia->delete();
        $this->log($cliente, ActivityEvent::ClientReferenceDeleted, 'Referencia de cliente eliminada', [
            'related' => ['type' => 'cliente_referencia', 'id' => $id],
        ]);
    }

    public function storeVinculo(Cliente $cliente, array $data): ClienteVinculo
    {
        $vinculo = $cliente->vinculos()->create($data);
        $this->log($cliente, ActivityEvent::ClientLinkCreated, 'Persona vinculada al expediente', [
            'related' => ['type' => 'cliente_vinculo', 'id' => $vinculo->id],
        ]);

        return $vinculo;
    }

    public function deleteVinculo(ClienteVinculo $vinculo): void
    {
        $cliente = $vinculo->cliente;
        $id = $vinculo->id;
        $vinculo->delete();
        $this->log($cliente, ActivityEvent::ClientLinkDeleted, 'Persona desvinculada del expediente', [
            'related' => ['type' => 'cliente_vinculo', 'id' => $id],
        ]);
    }

    public function storeGarantia(Cliente $cliente, array $data): ClienteGarantia
    {
        $garantia = $cliente->garantias()->create($data);
        $this->log($cliente, ActivityEvent::ClientGuaranteeCreated, 'Garantia creada', [
            'related' => ['type' => 'cliente_garantia', 'id' => $garantia->id],
        ]);

        return $garantia;
    }

    public function updateGarantia(ClienteGarantia $garantia, array $data): ClienteGarantia
    {
        $garantia->update($data);
        $this->log($garantia->cliente, ActivityEvent::ClientGuaranteeUpdated, 'Garantia actualizada', [
            'changed_fields' => $this->activityLog->fieldNames($data),
            'related' => ['type' => 'cliente_garantia', 'id' => $garantia->id],
        ]);

        return $garantia->refresh();
    }

    public function deleteGarantia(ClienteGarantia $garantia): void
    {
        $cliente = $garantia->cliente;
        $id = $garantia->id;
        $garantia->delete();
        $this->log($cliente, ActivityEvent::ClientGuaranteeDeleted, 'Garantia eliminada', [
            'related' => ['type' => 'cliente_garantia', 'id' => $id],
        ]);
    }

    private function log(Cliente $cliente, ActivityEvent $event, string $description, array $metadata): void
    {
        $this->activityLog->log(
            event: $event,
            description: $description,
            subject: $cliente,
            metadata: $metadata,
            causer: Auth::user(),
        );
    }
}
