<?php

namespace App\Http\Controllers;

use App\Enums\ClienteDocumentoEstado;
use App\Http\Requests\Clientes\StoreClienteDocumentoRequest;
use App\Http\Requests\Clientes\UpdateClienteDocumentoEstadoRequest;
use App\Models\Cliente;
use App\Models\ClienteDocumento;
use App\Services\ClienteDocumentoService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ClienteDocumentoController extends Controller
{
    public function store(StoreClienteDocumentoRequest $request, Cliente $cliente, ClienteDocumentoService $service)
    {
        $service->upload($cliente, $request->safe()->except('archivo'), $request->file('archivo'));

        return back()->with('success', 'Documento recibido');
    }

    public function updateStatus(
        UpdateClienteDocumentoEstadoRequest $request,
        Cliente $cliente,
        ClienteDocumento $documento,
        ClienteDocumentoService $service,
    ) {
        $this->ensureOwnership($cliente, $documento);
        $service->changeStatus(
            $documento,
            ClienteDocumentoEstado::from($request->validated('estado')),
            $request->user(),
            $request->validated('motivo_rechazo'),
        );

        return back()->with('success', 'Estado documental actualizado');
    }

    public function download(Cliente $cliente, ClienteDocumento $documento)
    {
        $this->ensureOwnership($cliente, $documento);
        Gate::authorize('view', $cliente);
        abort_unless($documento->path && Storage::disk($documento->disk)->exists($documento->path), 404);

        activity()
            ->performedOn($cliente)
            ->causedBy(request()->user())
            ->withProperties(['documento_id' => $documento->id])
            ->log('Documento de cliente descargado');

        return Storage::disk($documento->disk)->download($documento->path, $documento->nombre_original);
    }

    private function ensureOwnership(Cliente $cliente, ClienteDocumento $documento): void
    {
        abort_unless($documento->cliente_id === $cliente->id, 404);
    }
}
