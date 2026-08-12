<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\TransferClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Services\ClienteService;
use App\Services\PaisService;
use App\Services\RegimenFiscalService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ClienteController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:read clientes', only: ['index', 'show']),
            new Middleware('permission:create clientes', only: ['create', 'store']),
            new Middleware('permission:update clientes', only: ['edit', 'update']),
            new Middleware('permission:delete clientes', only: ['destroy']),
        ];
    }

    public function index(Request $request, ClienteService $clienteService)
    {
        $filters = $request->validate([
            'scope' => ['nullable', Rule::in(['current', 'all'])],
        ]);
        $scope = $filters['scope'] ?? 'current';
        if ($scope === 'current' && ! $request->user()->current_sucursal_id) {
            throw ValidationException::withMessages([
                'sucursal_id' => 'Selecciona una sucursal activa antes de consultar su cartera.',
            ]);
        }
        $clientes = $clienteService->readAll($scope === 'current' ? $request->user()->current_sucursal_id : null);

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
            $clientes[$key]['can_update'] = $request->user()->can('update', $cliente);
            $clientes[$key]['can_delete'] = $request->user()->can('delete', $cliente);
            $clientes[$key]['can_transfer'] = $request->user()->can('transfer clientes')
                && ($request->user()->is_super_admin
                    || (int) $request->user()->current_sucursal_id === (int) $cliente->sucursal_id);
        }

        return Inertia::render('Clientes/Index', [
            'clientes' => $clientes,
            'can' => [
                'create' => $request->user()->can('create', Cliente::class),
                'update' => $request->user()->can('update clientes'),
                'delete' => $request->user()->can('delete clientes'),
                'transfer' => $request->user()->can('transfer clientes'),
            ],
            'filters' => ['scope' => $scope],
            'sucursales' => fn () => $request->user()->can('transfer clientes')
                ? ($request->user()->is_super_admin
                    ? Sucursal::query()->where('activa', true)->orderBy('nombre')->get(['id', 'nombre', 'clave'])
                    : $request->user()->sucursales()->where('activa', true)->orderBy('nombre')->get(['sucursales.id', 'nombre', 'clave']))
                : [],
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
        Gate::authorize('view', $cliente);
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
        Gate::authorize('update', $cliente);
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
        Gate::authorize('delete', $cliente);
        $clienteService->destroy($cliente);

        return response()->redirectToRoute('clientes.index');
    }

    public function transfer(TransferClienteRequest $request, Cliente $cliente)
    {
        $actor = $request->user();
        $targetId = (int) $request->validated('sucursal_id');
        $sourceId = (int) $cliente->sucursal_id;

        if (! $actor->is_super_admin) {
            abort_unless(
                (int) $actor->current_sucursal_id === (int) $cliente->sucursal_id
                    && $actor->sucursales()->whereKey($targetId)->exists(),
                403,
                'Sólo puedes trasladar clientes entre tus sucursales asignadas.',
            );
        }

        $cliente->update(['sucursal_id' => $targetId]);
        app(\App\Services\ActivityLogService::class)->log(
            event: \App\Enums\ActivityEvent::ClientTransferred,
            description: 'Cliente trasladado de sucursal',
            subject: $cliente,
            metadata: [
                'changed_fields' => ['sucursal_id'],
                'related' => ['type' => 'sucursal', 'id' => $targetId],
                'state' => "from-sucursal:{$sourceId}",
            ],
            causer: $actor,
        );

        return back()->with('success', 'Cliente trasladado');
    }
}
