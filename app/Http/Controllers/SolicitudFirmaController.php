<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Http\Requests\RecibirSolicitudFirmaRequest;
use App\Http\Requests\RevisarSolicitudFirmaRequest;
use App\Models\Solicitud;
use App\Models\SolicitudFirma;
use App\Models\SolicitudPaquete;
use App\Services\ActivityLogService;
use App\Services\Documentos\RespuestaArchivoPrivado;
use App\Services\SolicitudFirmaService;
use Illuminate\Support\Facades\Gate;

class SolicitudFirmaController extends Controller
{
    public function store(RecibirSolicitudFirmaRequest $request, Solicitud $solicitud, SolicitudPaquete $paquete, SolicitudFirmaService $service)
    {
        $service->recibir($solicitud, $paquete, $request->safe()->except('archivo'), $request->file('archivo'), $request->user());

        return back()->with('success', 'Copia firmada recibida. Falta su revisión; la solicitud aún no está formalizada.');
    }

    public function review(RevisarSolicitudFirmaRequest $request, Solicitud $solicitud, SolicitudFirma $firma, SolicitudFirmaService $service)
    {
        $service->revisar($solicitud, $firma, $request->validated(), $request->user());

        return back()->with('success', $request->validated('estado_firma') === 'aceptada'
            ? 'Firma aceptada y formalización QA registrada. Todavía no hay desembolso.'
            : 'Copia rechazada. Conservamos la evidencia; recibe una copia corregida.');
    }

    public function file(Solicitud $solicitud, SolicitudFirma $firma, SolicitudFirmaService $service, RespuestaArchivoPrivado $files)
    {
        Gate::authorize('viewPackage', $solicitud);
        abort_unless($firma->paquete->solicitud_id === $solicitud->id, 404);
        $download = request()->routeIs('solicitudes.firmas.download');
        if ($download) {
            Gate::authorize('download documentos');
        }
        $service->verificarArchivo($firma);
        app(ActivityLogService::class)->log($download ? ActivityEvent::DocumentDownloaded : ActivityEvent::DocumentViewed,
            'Evidencia de firma consultada', $solicitud, ['related' => ['type' => 'solicitud_firma', 'id' => $firma->id]], request()->user(), sucursalId: $solicitud->sucursal_id);

        return $files->make($firma->disk, $firma->path, 'contrato-firmado-qa-'.$firma->id.'.pdf', $download);
    }
}
