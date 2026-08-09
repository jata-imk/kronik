<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\Cliente;
use App\Models\CodigoPostal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClienteService
{
    public function __construct(
        private readonly ClienteExpedienteService $expedienteService,
        private readonly ClienteDocumentoService $documentoService,
        private readonly ClienteConsentimientoSicService $consentimientoService,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function readAll()
    {
        return Cliente::with([
            'paisNacimiento',
            'datosFiscales.regimenFiscal',
            'direcciones' => [
                'pais',
                'codigoPostal.divisionAdministrativa.padre.padre',
                'divisionAdministrativaUno',
                'divisionAdministrativaDos',
                'divisionAdministrativaTres',
            ],
        ])->withCount(['vinculos', 'vinculosEntrantes'])->get();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $cliente = Cliente::create($data);

            $this->expedienteService->initializeChecklist($cliente);

            $cliente->datosFiscales()->create($data['datos_fiscales']);

            foreach ($data['direcciones'] as $direccion) {
                $direccion['entidad_id'] = $cliente->id;
                $direccion['entidad_tipo'] = 'clientes';

                $direccion = $this->completePostalData($direccion);

                if (! isset($direccion['tipo'])) {
                    $direccion['tipo'] = 'personal';
                }

                if (! isset($direccion['coordenadas'])) {
                    $direccion['coordenadas'] = [
                        'lat' => 0,
                        'lng' => 0,
                    ];
                }

                $cliente->direcciones()->create($direccion);
            }

            $this->activityLog->log(
                event: ActivityEvent::ClientCreated,
                description: 'Cliente creado',
                subject: $cliente,
                metadata: ['changed_fields' => $this->activityLog->fieldNames($data)],
                causer: Auth::user(),
            );

            return $cliente;
        });
    }

    public function update(Cliente $cliente, array $data)
    {
        return DB::transaction(function () use ($cliente, $data) {
            $cliente->update($data);

            if (isset($data['datos_fiscales'])) {
                $cliente->datosFiscales()->updateOrCreate([], $data['datos_fiscales']);
            }

            if (isset($data['direcciones'])) {
                foreach ($data['direcciones'] as $direccion) {
                    $direccion['entidad_id'] = $cliente->id;
                    $direccion['entidad_tipo'] = 'clientes';

                    $direccion = $this->completePostalData($direccion);

                    if (! isset($direccion['tipo'])) {
                        $direccion['tipo'] = 'personal';
                    }

                    if (! isset($direccion['coordenadas'])) {
                        $direccion['coordenadas'] = [
                            'lat' => 0,
                            'lng' => 0,
                        ];
                    }

                    $cliente->direcciones()->updateOrCreate(['tipo' => $direccion['tipo']], $direccion);
                }
            }

            $this->activityLog->log(
                event: ActivityEvent::ClientUpdated,
                description: 'Cliente actualizado',
                subject: $cliente,
                metadata: ['changed_fields' => $this->activityLog->fieldNames($data)],
                causer: Auth::user(),
            );

            return $cliente;
        });
    }

    public function destroy(Cliente $cliente)
    {
        return DB::transaction(function () use ($cliente) {
            $this->documentoService->deleteFilesFor($cliente);
            $this->consentimientoService->deleteFilesFor($cliente);
            $cliente->direcciones()->delete();
            $cliente->datosFiscales()->delete();
            $cliente->delete();

            $this->activityLog->log(
                event: ActivityEvent::ClientDeleted,
                description: 'Cliente eliminado',
                subject: $cliente,
                metadata: ['result' => 'deleted'],
                causer: Auth::user(),
            );
        });
    }

    private function completePostalData(array $direccion): array
    {
        if (blank($direccion['codigo_postal_id'] ?? null) && blank($direccion['codigo_postal'] ?? null)) {
            return $direccion;
        }

        $codigoPostal = CodigoPostal::query()
            ->when(
                filled($direccion['codigo_postal_id'] ?? null),
                fn ($query) => $query->whereKey($direccion['codigo_postal_id']),
            )
            ->when(
                filled($direccion['codigo_postal'] ?? null),
                fn ($query) => $query->where('codigo', $direccion['codigo_postal']),
            )
            ->when(
                filled($direccion['division_admin_tres_id'] ?? null),
                fn ($query) => $query->where('division_admin_id', $direccion['division_admin_tres_id']),
            )
            ->firstOrFail();

        $direccion['codigo_postal_id'] = $codigoPostal->id;
        $direccion['pais_id'] = $codigoPostal->pais_id;

        return $direccion;
    }
}
