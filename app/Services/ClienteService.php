<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\CodigoPostal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClienteService
{
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
                'divisionAdministrativaTres'
            ]
        ])->get();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $cliente = Cliente::create($data);

            $cliente->datosFiscales()->create($data['datos_fiscales']);

            foreach ($data['direcciones'] as $direccion) {
                $direccion['entidad_id'] = $cliente->id;
                $direccion['entidad_tipo'] = 'clientes';

                if (!isset($direccion['codigo_postal_id'])) {
                    $codigoPostal = CodigoPostal::where('codigo', $direccion['codigo_postal'])
                        ->where('division_admin_id', $direccion['division_admin_tres_id'])
                        ->first();

                    $direccion['codigo_postal_id'] = $codigoPostal->id;
                    $direccion['pais_id'] = $codigoPostal->pais_id;
                }

                if (!isset($direccion['tipo'])) {
                    $direccion['tipo'] = 'personal';
                }

                if (!isset($direccion['coordenadas'])) {
                    $direccion['coordenadas'] = [
                        'lat' => 0,
                        'lng' => 0
                    ];
                }

                $cliente->direcciones()->create($direccion);
            }

            activity()
                ->performedOn($cliente)
                ->causedBy(Auth::user())
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                    'attributes' => $data
                ])
                ->log('Cliente creado');

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

                    if (!isset($direccion['codigo_postal_id'])) {
                        $codigoPostal = CodigoPostal::where('codigo', $direccion['codigo_postal'])
                            ->where('division_admin_id', $direccion['division_admin_tres_id'])
                            ->first();

                        $direccion['codigo_postal_id'] = $codigoPostal->id;
                        $direccion['pais_id'] = $codigoPostal->pais_id;
                    }

                    if (!isset($direccion['tipo'])) {
                        $direccion['tipo'] = 'personal';
                    }

                    if (!isset($direccion['coordenadas'])) {
                        $direccion['coordenadas'] = [
                            'lat' => 0,
                            'lng' => 0
                        ];
                    }

                    $cliente->direcciones()->updateOrCreate(['tipo' => $direccion['tipo']], $direccion);
                }
            }

            activity()
                ->performedOn($cliente)
                ->causedBy(Auth::user())
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                    'attributes' => $data
                ])
                ->log('Cliente actualizado');

            return $cliente;
        });
    }

    public function destroy(Cliente $cliente)
    {
        return DB::transaction(function () use ($cliente) {
            $cliente->direcciones()->delete();
            $cliente->datosFiscales()->delete();
            $cliente->delete();

            activity()
                ->performedOn($cliente)
                ->causedBy(Auth::user())
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                ])
                ->log('Cliente eliminado');
        });
    }
}
