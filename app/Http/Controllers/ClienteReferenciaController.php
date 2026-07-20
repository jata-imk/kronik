<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\ClienteReferenciaRequest;
use App\Models\Cliente;
use App\Models\ClienteReferencia;
use App\Services\ClienteExpedienteService;

class ClienteReferenciaController extends Controller
{
    public function store(ClienteReferenciaRequest $request, Cliente $cliente, ClienteExpedienteService $service)
    {
        $service->storeReferencia($cliente, $request->validated());

        return back()->with('success', 'Referencia creada');
    }

    public function update(ClienteReferenciaRequest $request, Cliente $cliente, ClienteReferencia $referencia, ClienteExpedienteService $service)
    {
        $this->ensureOwnership($cliente, $referencia);
        $service->updateReferencia($referencia, $request->validated());

        return back()->with('success', 'Referencia actualizada');
    }

    public function destroy(Cliente $cliente, ClienteReferencia $referencia, ClienteExpedienteService $service)
    {
        abort_unless(request()->user()->can('update', $cliente), 403);
        $this->ensureOwnership($cliente, $referencia);
        $service->deleteReferencia($referencia);

        return back()->with('success', 'Referencia eliminada');
    }

    private function ensureOwnership(Cliente $cliente, ClienteReferencia $referencia): void
    {
        abort_unless($referencia->cliente_id === $cliente->id, 404);
    }
}
