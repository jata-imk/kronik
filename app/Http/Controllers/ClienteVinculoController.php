<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\ClienteVinculoRequest;
use App\Models\Cliente;
use App\Models\ClienteVinculo;
use App\Services\ClienteExpedienteService;

class ClienteVinculoController extends Controller
{
    public function store(ClienteVinculoRequest $request, Cliente $cliente, ClienteExpedienteService $service)
    {
        $service->storeVinculo($cliente, $request->validated());

        return back()->with('success', 'Persona vinculada');
    }

    public function destroy(Cliente $cliente, ClienteVinculo $vinculo, ClienteExpedienteService $service)
    {
        abort_unless(request()->user()->can('update', $cliente), 403);
        abort_unless($vinculo->cliente_id === $cliente->id, 404);
        $service->deleteVinculo($vinculo);

        return back()->with('success', 'Persona desvinculada');
    }
}
