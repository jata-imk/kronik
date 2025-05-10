<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Models\CodigoPostal;
use App\Models\RegimenFiscal;
use App\Services\ClienteService;
use App\Services\PaisService;
use App\Services\RegimenFiscalService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteController extends Controller
{
    public function index(Request $request, ClienteService $clienteService)
    {
        $clientes = $clienteService->readAll();

        foreach ($clientes as $key => $cliente) {
            $clientes[$key]['nombre_completo'] = implode(' ', array_filter([
                $cliente['primer_nombre'],
                $cliente['segundo_nombre'],
                $cliente['apellido_paterno'],
                $cliente['apellido_materno']
            ]));
        }

        return Inertia::render('Clientes/Index', [
            'clientes' => $clientes,
        ]);
    }

    public function create(PaisService $paisService, RegimenFiscalService $regimenFiscalService)
    {
        $paises = $paisService->readAll(['id', 'nombre_es', 'nombre_nativo', 'codigo_iso', 'emoji']);
        $sexos = [
            ['value' => 'masculino', 'label' => 'Masculino'],
            ['value' => 'femenino', 'label' => 'Femenino']
        ];

        $tiposPersona = [
            ['value' => 'fisica', 'label' => 'Fisica'],
            ['value' => 'moral', 'label' => 'Moral']
        ];

        $regimenesFiscales = $regimenFiscalService->readAll(['id', 'clave', 'descripcion', 'fisica', 'moral']);

        return Inertia::render('Clientes/Create', [
            'paises' => $paises,
            'sexos' => $sexos,
            'tiposPersona' => $tiposPersona,
            'regimenesFiscales' => $regimenesFiscales
        ]);
    }

    public function store(StoreClienteRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $cliente = Cliente::create($request->validated());

            $cliente->datosFiscales()->create($request->validated()['datos_fiscales']);

            foreach ($request->validated()['direcciones'] as $direccion) {
                $direccion['entidad_id'] = $cliente->id;
                $direccion['entidad_tipo'] = 'clientes';

                if (!isset($direccion['codigo_postal_id'])) {
                    $codigoPostal = CodigoPostal::where('codigo', $direccion['codigo_postal'])->where('division_admin_id', $direccion['division_admin_tres_id'])->first();

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

            return response()->redirectToRoute('clientes.index');
        });
    }

    public function show(Cliente $cliente, PaisService $paisService, RegimenFiscalService $regimenFiscalService)
    {
        $paises = $paisService->readAll(['id', 'nombre_es', 'nombre_nativo', 'codigo_iso', 'emoji']);
        $sexos = [
            ['value' => 'masculino', 'label' => 'Masculino'],
            ['value' => 'femenino', 'label' => 'Femenino']
        ];

        $tiposPersona = [
            ['value' => 'fisica', 'label' => 'Fisica'],
            ['value' => 'moral', 'label' => 'Moral']
        ];

        $regimenesFiscales = $regimenFiscalService->readAll(['id', 'clave', 'descripcion', 'fisica', 'moral']);

        return Inertia::render('Clientes/Show', [
            'readOnly' => true,
            'cliente' => $cliente->load(['datosFiscales', 'direcciones.pais', 'direcciones.codigoPostal.divisionAdministrativa.padre.padre']),
            'paises' => $paises,
            'sexos' => $sexos,
            'tiposPersona' => $tiposPersona,
            'regimenesFiscales' => $regimenesFiscales
        ]);
    }

    public function edit(Cliente $cliente, PaisService $paisService, RegimenFiscalService $regimenFiscalService)
    {
        $paises = $paisService->readAll(['id', 'nombre_es', 'nombre_nativo', 'codigo_iso', 'emoji']);
        $sexos = [
            ['value' => 'masculino', 'label' => 'Masculino'],
            ['value' => 'femenino', 'label' => 'Femenino']
        ];

        $tiposPersona = [
            ['value' => 'fisica', 'label' => 'Fisica'],
            ['value' => 'moral', 'label' => 'Moral']
        ];

        $regimenesFiscales = $regimenFiscalService->readAll(['id', 'clave', 'descripcion', 'fisica', 'moral']);

        return Inertia::render('Clientes/Update', [
            'action' => 'clientes.update',
            'readOnly' => false,
            'cliente' => $cliente->load(['datosFiscales', 'direcciones.pais', 'direcciones.codigoPostal.divisionAdministrativa.padre.padre']),
            'paises' => $paises,
            'sexos' => $sexos,
            'tiposPersona' => $tiposPersona,
            'regimenesFiscales' => $regimenesFiscales
        ]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        return DB::transaction(function () use ($request, $cliente) {
            $cliente->update($request->validated());

            if ($request->has('datos_fiscales')) {
                $cliente->datosFiscales()->updateOrCreate([], $request->validated()['datos_fiscales']);
            }

            if ($request->has('direcciones')) {
                foreach ($request->validated()['direcciones'] as $direccion) {
                    $direccion['entidad_id'] = $cliente->id;
                    $direccion['entidad_tipo'] = 'clientes';

                    if (!isset($direccion['codigo_postal_id'])) {
                        $codigoPostal = CodigoPostal::where('codigo', $direccion['codigo_postal'])->where('division_admin_id', $direccion['division_admin_tres_id'])->first();

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

            return response()->redirectToRoute('clientes.index');
        });
    }

    public function destroy(Cliente $cliente)
    {
        return DB::transaction(function () use ($cliente) {
            $cliente->direcciones()->delete();
            $cliente->datosFiscales()->delete();
            $cliente->delete();

            return response()->json(null, 204);
        });
    }
}
