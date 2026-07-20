<?php

namespace App\Services;

use App\Enums\ClienteDocumentoTipo;
use App\Models\Cliente;
use App\Models\ClienteGarantia;
use App\Models\ClienteReferencia;
use App\Models\ClienteVinculo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClienteExpedienteService
{
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
            $before = $cliente->only(array_keys($data));
            $cliente->update($data);

            $this->log($cliente, 'Perfil KYC actualizado', [
                'campos' => array_keys($data),
                'antes' => $before,
            ]);

            return $cliente->refresh();
        });
    }

    public function storeReferencia(Cliente $cliente, array $data): ClienteReferencia
    {
        $referencia = $cliente->referencias()->create($data);
        $this->log($cliente, 'Referencia de cliente creada', ['referencia_id' => $referencia->id]);

        return $referencia;
    }

    public function updateReferencia(ClienteReferencia $referencia, array $data): ClienteReferencia
    {
        $referencia->update($data);
        $this->log($referencia->cliente, 'Referencia de cliente actualizada', ['referencia_id' => $referencia->id]);

        return $referencia->refresh();
    }

    public function deleteReferencia(ClienteReferencia $referencia): void
    {
        $cliente = $referencia->cliente;
        $id = $referencia->id;
        $referencia->delete();
        $this->log($cliente, 'Referencia de cliente eliminada', ['referencia_id' => $id]);
    }

    public function storeVinculo(Cliente $cliente, array $data): ClienteVinculo
    {
        $vinculo = $cliente->vinculos()->create($data);
        $this->log($cliente, 'Persona vinculada al expediente', ['vinculo_id' => $vinculo->id]);

        return $vinculo;
    }

    public function deleteVinculo(ClienteVinculo $vinculo): void
    {
        $cliente = $vinculo->cliente;
        $id = $vinculo->id;
        $vinculo->delete();
        $this->log($cliente, 'Persona desvinculada del expediente', ['vinculo_id' => $id]);
    }

    public function storeGarantia(Cliente $cliente, array $data): ClienteGarantia
    {
        $garantia = $cliente->garantias()->create($data);
        $this->log($cliente, 'Garantia creada', ['garantia_id' => $garantia->id]);

        return $garantia;
    }

    public function updateGarantia(ClienteGarantia $garantia, array $data): ClienteGarantia
    {
        $garantia->update($data);
        $this->log($garantia->cliente, 'Garantia actualizada', ['garantia_id' => $garantia->id]);

        return $garantia->refresh();
    }

    public function deleteGarantia(ClienteGarantia $garantia): void
    {
        $cliente = $garantia->cliente;
        $id = $garantia->id;
        $garantia->delete();
        $this->log($cliente, 'Garantia eliminada', ['garantia_id' => $id]);
    }

    private function log(Cliente $cliente, string $message, array $properties): void
    {
        activity()
            ->performedOn($cliente)
            ->causedBy(Auth::user())
            ->withProperties($properties)
            ->log($message);
    }
}
