<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\StoreClienteConsentimientoSicRequest;
use App\Models\Cliente;
use App\Models\ClienteConsentimientoSic;
use App\Services\ClienteConsentimientoSicService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ClienteConsentimientoSicController extends Controller
{
    public function store(StoreClienteConsentimientoSicRequest $request, Cliente $cliente, ClienteConsentimientoSicService $service)
    {
        $service->store(
            $cliente,
            $request->safe()->except('evidencia'),
            $request->file('evidencia'),
            $request->user(),
        );

        return back()->with('success', 'Consentimiento SIC registrado');
    }

    public function revoke(Cliente $cliente, ClienteConsentimientoSic $consentimiento, ClienteConsentimientoSicService $service)
    {
        abort_unless(request()->user()->can('update', $cliente), 403);
        $this->ensureOwnership($cliente, $consentimiento);
        abort_if($consentimiento->revocado_en, 422, 'El consentimiento ya fue revocado.');
        $service->revoke($consentimiento, request()->user());

        return back()->with('success', 'Consentimiento SIC revocado');
    }

    public function download(Cliente $cliente, ClienteConsentimientoSic $consentimiento)
    {
        $this->ensureOwnership($cliente, $consentimiento);
        Gate::authorize('view', $cliente);
        abort_unless(Storage::disk($consentimiento->evidencia_disk)->exists($consentimiento->evidencia_path), 404);

        activity()
            ->performedOn($cliente)
            ->causedBy(request()->user())
            ->withProperties(['consentimiento_id' => $consentimiento->id])
            ->log('Evidencia de consentimiento SIC descargada');

        return Storage::disk($consentimiento->evidencia_disk)
            ->download($consentimiento->evidencia_path, $consentimiento->evidencia_nombre_original);
    }

    private function ensureOwnership(Cliente $cliente, ClienteConsentimientoSic $consentimiento): void
    {
        abort_unless($consentimiento->cliente_id === $cliente->id, 404);
    }
}
