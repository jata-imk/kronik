<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Models\Cliente;
use App\Services\ClienteService;
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
            new Middleware('role_or_permission:Super Admin|read clientes', only: ['index', 'show']),
            new Middleware('role_or_permission:Super Admin|create clientes', only: ['create', 'store']),
            new Middleware('role_or_permission:Super Admin|update clientes', only: ['edit', 'update']),
            new Middleware('role_or_permission:Super Admin|delete clientes', only: ['destroy']),
        ];
    }

    public function index(Request $request, ClienteService $clienteService)
    {
        $clientes = $clienteService->readAll();

        foreach ($clientes as $key => $cliente) {
            $clientes[$key]['nombre_completo'] = implode(' ', array_filter([
                $cliente['primer_nombre'],
                $cliente['segundo_nombre'],
                $cliente['apellido_paterno'],
                $cliente['apellido_materno'],
            ]));
            $clientes[$key]['relaciones_count'] =
                (int) $cliente['vinculos_count'] +
                (int) $cliente['vinculos_entrantes_count'];
        }

        return Inertia::render('Clientes/Index', [
            'clientes' => $clientes,
            'can' => [
                'create' => $request->user()->can('create', Cliente::class),
                'update' => $request->user()->can('update clientes'),
                'delete' => $request->user()->can('delete clientes'),
            ],
        ]);
    }

    public function create(Request $request, PaisService $paisService, RegimenFiscalService $regimenFiscalService)
    {
        $paises = $paisService->readAll(['id', 'nombre_es', 'nombre_nativo', 'codigo_iso', 'emoji']);
        $sexos = [
            ['value' => 'masculino', 'label' => 'Masculino'],
            ['value' => 'femenino', 'label' => 'Femenino'],
        ];

        $tiposPersona = [
            ['value' => 'fisica', 'label' => 'Fisica'],
            ['value' => 'moral', 'label' => 'Moral'],
        ];

        $regimenesFiscales = $regimenFiscalService->readAll(['id', 'clave', 'descripcion', 'fisica', 'moral']);

        return Inertia::render('Clientes/Create', [
            'paises' => $paises,
            'sexos' => $sexos,
            'tiposPersona' => $tiposPersona,
            'regimenesFiscales' => $regimenesFiscales,
        ]);
    }

    public function store(StoreClienteRequest $request, ClienteService $clienteService)
    {
        $cliente = $clienteService->store($request->validated());

        return response()->redirectToRoute('clientes.expediente.show', ['cliente' => $cliente->id]);
    }

    public function show(Request $request, Cliente $cliente, PaisService $paisService, RegimenFiscalService $regimenFiscalService)
    {
        $paises = $paisService->readAll(['id', 'nombre_es', 'nombre_nativo', 'codigo_iso', 'emoji']);
        $sexos = [
            ['value' => 'masculino', 'label' => 'Masculino'],
            ['value' => 'femenino', 'label' => 'Femenino'],
        ];

        $tiposPersona = [
            ['value' => 'fisica', 'label' => 'Fisica'],
            ['value' => 'moral', 'label' => 'Moral'],
        ];

        $regimenesFiscales = $regimenFiscalService->readAll(['id', 'clave', 'descripcion', 'fisica', 'moral']);

        return Inertia::render('Clientes/Show', [
            'readOnly' => true,
            'cliente' => $cliente->load(['datosFiscales', 'direcciones.pais', 'direcciones.codigoPostal.divisionAdministrativa.padre.padre']),
            'paises' => $paises,
            'sexos' => $sexos,
            'tiposPersona' => $tiposPersona,
            'regimenesFiscales' => $regimenesFiscales,
        ]);
    }

    public function edit(Request $request, Cliente $cliente, PaisService $paisService, RegimenFiscalService $regimenFiscalService)
    {
        $paises = $paisService->readAll(['id', 'nombre_es', 'nombre_nativo', 'codigo_iso', 'emoji']);
        $sexos = [
            ['value' => 'masculino', 'label' => 'Masculino'],
            ['value' => 'femenino', 'label' => 'Femenino'],
        ];

        $tiposPersona = [
            ['value' => 'fisica', 'label' => 'Fisica'],
            ['value' => 'moral', 'label' => 'Moral'],
        ];

        $regimenesFiscales = $regimenFiscalService->readAll(['id', 'clave', 'descripcion', 'fisica', 'moral']);

        return Inertia::render('Clientes/Update', [
            'action' => 'clientes.update',
            'readOnly' => false,
            'cliente' => $cliente->load(['datosFiscales', 'direcciones.pais', 'direcciones.codigoPostal.divisionAdministrativa.padre.padre']),
            'paises' => $paises,
            'sexos' => $sexos,
            'tiposPersona' => $tiposPersona,
            'regimenesFiscales' => $regimenesFiscales,
        ]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente, ClienteService $clienteService)
    {
        $clienteService->update($cliente, $request->validated());

        return response()->redirectToRoute('clientes.edit', ['cliente' => $cliente->id]);
    }

    public function destroy(Cliente $cliente, ClienteService $clienteService)
    {
        $clienteService->destroy($cliente);

        return response()->redirectToRoute('clientes.index');
    }
}
