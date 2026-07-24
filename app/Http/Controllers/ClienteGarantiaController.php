<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\ClienteGarantiaRequest;
use App\Models\Cliente;
use App\Models\ClienteGarantia;
use App\Services\ClienteExpedienteService;

class ClienteGarantiaController extends Controller
{
    public function store(ClienteGarantiaRequest $request, Cliente $cliente, ClienteExpedienteService $service)
    {
        $service->storeGarantia($cliente, $request->validated());

        return back()->with('success', 'Garantia creada');
    }

    public function update(ClienteGarantiaRequest $request, Cliente $cliente, ClienteGarantia $garantia, ClienteExpedienteService $service)
    {
        $this->ensureOwnership($cliente, $garantia);
        $service->updateGarantia($garantia, $request->validated());

        return back()->with('success', 'Garantia actualizada');
    }

    public function destroy(Cliente $cliente, ClienteGarantia $garantia, ClienteExpedienteService $service)
    {
        abort_unless(request()->user()->can('update', $cliente), 403);
        $this->ensureOwnership($cliente, $garantia);
        $service->deleteGarantia($garantia);

        return back()->with('success', 'Garantia eliminada');
    }

    private function ensureOwnership(Cliente $cliente, ClienteGarantia $garantia): void
    {
        abort_unless($garantia->cliente_id === $cliente->id, 404);
    }
}
