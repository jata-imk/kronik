<?php

namespace App\Http\Controllers;

use App\Enums\DocumentoGeneradoEstado;
use App\Enums\DocumentoPlantillaTipo;
use App\Enums\DocumentoPlantillaVersionEstado;
use App\Http\Requests\PrepararSolicitudPaqueteRequest;
use App\Models\DocumentoPlantillaVersion;
use App\Models\Solicitud;
use App\Models\SolicitudFirma;
use App\Models\SolicitudPaquete;
use App\Services\SolicitudFirmaService;
use App\Services\SolicitudPaqueteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SolicitudPaqueteController extends Controller
{
    public function show(Request $request, Solicitud $solicitud, SolicitudPaqueteService $service)
    {
        Gate::authorize('viewPackage', $solicitud);
        $solicitud->load(['cliente', 'sucursal']);
        $resolucionId = $solicitud->resoluciones()->latest('id')->value('id');
        $paquetes = SolicitudPaquete::where('solicitud_id', $solicitud->id)->with('documento')->latest('id')->paginate(10);
        $actual = SolicitudPaquete::where('solicitud_resolucion_id', $resolucionId)->with('documento')->first();
        $firmas = SolicitudFirma::with(['receptor:id,name', 'revisor:id,name'])->whereIn('solicitud_paquete_id', SolicitudPaquete::where('solicitud_id', $solicitud->id)->select('id'))
            ->latest('id')->paginate(10, ['*'], 'firmas_page')->withQueryString()->through(fn ($firma) => [
                'id' => $firma->id, 'paquete_id' => $firma->solicitud_paquete_id, 'estado' => $firma->estado,
                'actual' => $firma->solicitud_paquete_id === $actual?->id, 'fecha_firma' => $firma->fecha_firma->toDateString(),
                'recibida_en' => $firma->created_at, 'revisada_en' => $firma->revisada_en, 'motivo' => $firma->motivo,
                'recibida_por' => $firma->recibida_por, 'revisada_por' => $firma->revisada_por, 'archivo_hash' => $firma->archivo_hash,
                'receptor' => $firma->receptor?->name, 'revisor' => $firma->revisor?->name,
                'view_url' => route('solicitudes.firmas.view', [$solicitud, $firma]),
                'download_url' => $request->user()->can('download documentos') ? route('solicitudes.firmas.download', [$solicitud, $firma]) : null,
            ]);
        $serialize = function (SolicitudPaquete $paquete) use ($resolucionId, $request) {
            $doc = $paquete->documento;
            $ready = $doc?->estado === DocumentoGeneradoEstado::Generado;

            return ['id' => $paquete->id, 'actual' => $paquete->solicitud_resolucion_id === $resolucionId,
                'created_at' => $paquete->created_at, 'snapshot_hash' => $paquete->snapshot_hash,
                'plantilla' => $paquete->snapshot['plantilla']['nombre'], 'version' => $paquete->snapshot['plantilla']['numero'],
                'documento' => $doc ? ['id' => $doc->id, 'estado' => $doc->estado->value, 'error_mensaje' => $doc->error_mensaje,
                    'nombre_archivo' => $doc->nombre_archivo, 'status_url' => route('documentos-generados.status', $doc),
                    'view_url' => $ready ? route('documentos-generados.view', $doc) : null,
                    'download_url' => $ready && $request->user()->can('download', $doc) ? route('documentos-generados.download', $doc) : null] : null];
        };

        return Inertia::render('Solicitudes/Paquete', [
            'solicitud' => ['id' => $solicitud->id, 'estado' => $solicitud->estado->value, 'lock_version' => $solicitud->lock_version,
                'cliente_id' => $solicitud->cliente_id, 'cliente' => trim($solicitud->cliente->primer_nombre.' '.$solicitud->cliente->apellido_paterno.' '.$solicitud->cliente->apellido_materno),
                'sucursal' => $solicitud->sucursal->nombre],
            'preparacion' => $service->requisitos($solicitud, $request->user()),
            'actual' => $actual ? [...$serialize($actual), 'tabla' => $actual->snapshot['tabla']] : null,
            'firmas' => $firmas,
            'formalizacion' => $actual?->formalizacion?->only(['id', 'modo', 'created_at', 'snapshot_hash']),
            'creditoId' => Gate::allows('viewAny', \App\Models\Credito::class) ? $solicitud->credito()->value('id') : null,
            'firmaRequisitos' => $actual ? app(SolicitudFirmaService::class)->requisitos($solicitud, $actual, $request->user(),
                Gate::allows('reviewSignature', $solicitud) ? 'reviewSignature' : 'receiveSignature') : null,
            'firmaPendiente' => $actual?->firmas()->where('estado', 'recibida')->exists() ?? false,
            'maxArchivoKb' => config('documentos.max_upload_kb'),
            'paquetes' => $paquetes->through($serialize),
            'plantillas' => Gate::allows('preparePackage', $solicitud) ? DocumentoPlantillaVersion::with('plantilla:id,nombre')
                ->where('estado', DocumentoPlantillaVersionEstado::Activa)
                ->whereHas('plantilla', fn ($q) => $q->where('tipo', DocumentoPlantillaTipo::Contrato)->where('activa', true))
                ->get()->map(fn ($v) => ['id' => $v->id, 'label' => $v->plantilla->nombre.' · v'.$v->numero]) : [],
            'can' => ['preparar' => Gate::allows('preparePackage', $solicitud),
                'desembolso' => Gate::allows('viewAny', \App\Models\Credito::class),
                'recibirFirma' => Gate::allows('receiveSignature', $solicitud),
                'revisarFirma' => Gate::allows('reviewSignature', $solicitud)],
        ]);
    }

    public function store(PrepararSolicitudPaqueteRequest $request, Solicitud $solicitud, SolicitudPaqueteService $service)
    {
        $service->preparar($solicitud, $request->validated(), $request->user());

        return redirect()->route('solicitudes.paquete.show', $solicitud)->with('success', 'Paquete QA solicitado. La solicitud continúa aprobada, sin formalizar.');
    }

    public function retry(Request $request, Solicitud $solicitud, SolicitudPaquete $paquete, SolicitudPaqueteService $service)
    {
        $service->reintentar($solicitud, $paquete, $request->user());

        return back()->with('success', 'Se reintentará la generación con los mismos datos congelados.');
    }
}
