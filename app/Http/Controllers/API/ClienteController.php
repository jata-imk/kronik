<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use App\Services\ClienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClienteController extends Controller
{
    public function index(Request $request, ClienteService $clienteService)
    {
        Gate::authorize('viewAny', Cliente::class);

        if ($request->hasAny(['scope', 'sucursal_id', 'por_pagina', 'buscar'])) {
            $data = $request->validate(['por_pagina' => ['nullable', 'integer', \Illuminate\Validation\Rule::in([10, 25, 50])], 'buscar' => 'nullable|string|max:100']);
            $scope = app(\App\Services\ConsultaSucursal::class)->resolver($request, 'all');
            if ($scope['requiereSucursal']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['sucursal_id' => 'Selecciona una sucursal activa antes de consultar su cartera.']);
            }
            $query = app(\App\Services\ConsultaSucursal::class)->aplicar($clienteService->query(), $scope);
            if ($buscar = $data['buscar'] ?? null) {
                $query->where(fn ($q) => $q->where('primer_nombre', 'like', '%'.$buscar.'%')->orWhere('apellido_paterno', 'like', '%'.$buscar.'%'));
            }

            return ClienteResource::collection($query->orderBy('id')->paginate($data['por_pagina'] ?? 25)->withQueryString());
        }

        return ClienteResource::collection($clienteService->readAll());
    }

    public function store(StoreClienteRequest $request, ClienteService $clienteService)
    {
        $cliente = $clienteService->store($request->validated());

        return (new ClienteResource($cliente->load(['paisNacimiento', 'datosFiscales', 'direcciones'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Cliente $cliente)
    {
        Gate::authorize('view', $cliente);

        return new ClienteResource($cliente->load(['paisNacimiento', 'datosFiscales', 'direcciones']));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente, ClienteService $clienteService)
    {
        $clienteService->update($cliente, $request->validated());

        return new ClienteResource($cliente->fresh()->load(['paisNacimiento', 'datosFiscales', 'direcciones']));
    }

    public function destroy(Cliente $cliente, ClienteService $clienteService)
    {
        Gate::authorize('delete', $cliente);
        $clienteService->destroy($cliente);

        return response()->json(null, 204);
    }
}
