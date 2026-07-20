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
