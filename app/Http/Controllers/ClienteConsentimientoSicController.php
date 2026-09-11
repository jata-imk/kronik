<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Http\Requests\Clientes\StoreClienteConsentimientoSicRequest;
use App\Models\Cliente;
use App\Models\ClienteConsentimientoSic;
use App\Services\ActivityLogService;
use App\Services\ClienteConsentimientoSicService;
use App\Services\Documentos\RespuestaArchivoPrivado;
use Illuminate\Support\Facades\Gate;

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

    public function view(Cliente $cliente, ClienteConsentimientoSic $consentimiento, RespuestaArchivoPrivado $files)
    {
        $this->ensureOwnership($cliente, $consentimiento);
        Gate::authorize('view', $cliente);

        app(ActivityLogService::class)->log(
            event: ActivityEvent::ClientSicConsentEvidenceViewed,
            description: 'Evidencia de consentimiento SIC visualizada',
            subject: $cliente,
            metadata: ['related' => ['type' => 'cliente_consentimiento_sic', 'id' => $consentimiento->id]],
            causer: request()->user(),
        );

        return $files->make($consentimiento->evidencia_disk, $consentimiento->evidencia_path, $consentimiento->evidencia_nombre_original);
    }

    public function download(Cliente $cliente, ClienteConsentimientoSic $consentimiento, RespuestaArchivoPrivado $files)
    {
        $this->ensureOwnership($cliente, $consentimiento);
        Gate::authorize('view', $cliente);

        app(ActivityLogService::class)->log(
            event: ActivityEvent::ClientSicConsentEvidenceDownloaded,
            description: 'Evidencia de consentimiento SIC descargada',
            subject: $cliente,
            metadata: ['related' => ['type' => 'cliente_consentimiento_sic', 'id' => $consentimiento->id]],
            causer: request()->user(),
        );

        return $files->make($consentimiento->evidencia_disk, $consentimiento->evidencia_path, $consentimiento->evidencia_nombre_original, true);
    }

    private function ensureOwnership(Cliente $cliente, ClienteConsentimientoSic $consentimiento): void
    {
        abort_unless($consentimiento->cliente_id === $cliente->id, 404);
    }
}
