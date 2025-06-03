<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Models\CodigoPostal;
use App\Services\ClienteService;
use App\Services\MenubarService;
use App\Services\PaisService;
use App\Services\RegimenFiscalService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class ClienteController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role_or_permission:Super Admin|read clientes', only: ['index']),
            new Middleware('role_or_permission:Super Admin|create clientes', only: ['create', 'store']),
            new Middleware('role_or_permission:Super Admin|update clientes', only: ['edit', 'update']),
            new Middleware('role_or_permission:Super Admin|delete clientes', only: ['destroy']),
        ];
    }

    public function index(Request $request, ClienteService $clienteService, MenubarService $menubarService)
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
            'menubarItems' => $menubarService->getMenuItems($request),
            'clientes' => $clientes,
        ]);
    }

    public function create(Request $request, PaisService $paisService, RegimenFiscalService $regimenFiscalService, MenubarService $menubarService)
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
            'menubarItems' => $menubarService->getMenuItems($request),
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

            return response()->redirectToRoute('clientes.edit', ['cliente' => $cliente->id]);
        });
    }

    public function show(Request $request, Cliente $cliente, PaisService $paisService, RegimenFiscalService $regimenFiscalService, MenubarService $menubarService)
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
            'menubarItems' => $menubarService->getMenuItems($request),
            'readOnly' => true,
            'cliente' => $cliente->load(['datosFiscales', 'direcciones.pais', 'direcciones.codigoPostal.divisionAdministrativa.padre.padre']),
            'paises' => $paises,
            'sexos' => $sexos,
            'tiposPersona' => $tiposPersona,
            'regimenesFiscales' => $regimenesFiscales
        ]);
    }

    public function edit(Request $request, Cliente $cliente, PaisService $paisService, RegimenFiscalService $regimenFiscalService, MenubarService $menubarService)
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
            'menubarItems' => $menubarService->getMenuItems($request),
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

            return response()->redirectToRoute('clientes.edit', ['cliente' => $cliente->id]);
        });
    }

    public function destroy(Cliente $cliente)
    {
        return DB::transaction(function () use ($cliente) {
            $cliente->direcciones()->delete();
            $cliente->datosFiscales()->delete();
            $cliente->delete();

            return response()->redirectToRoute('clientes.index');
        });
    }
}
