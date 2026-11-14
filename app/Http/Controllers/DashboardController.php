<?php

namespace App\Http\Controllers;

use App\Enums\SolicitudEstado;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $consulta = app(\App\Services\ConsultaSucursal::class);
        $alcance = $consulta->resolver($request);
        $puedeClientes = Gate::allows('viewAny', Cliente::class);
        $puedeSolicitudes = Gate::allows('viewAny', Solicitud::class);
        $puedeCreditos = Gate::allows('viewAny', Credito::class);
        $scope = fn ($query) => $consulta->aplicar($query, $alcance);

        $solicitudes = $puedeSolicitudes ? $scope(Solicitud::query()) : null;
        $creditos = $puedeCreditos ? $scope(Credito::query()) : null;

        return Inertia::render('Dashboard', [
            'filters' => $alcance,
            'sucursales' => $consulta->opciones(),
            'resumen' => [
                'clientes' => $puedeClientes ? $scope(Cliente::query())->count() : null,
                'solicitudes' => $solicitudes?->count(),
                'enRevision' => $solicitudes ? (clone $solicitudes)->where('estado', SolicitudEstado::EnRevision)->count() : null,
                'porFormalizar' => $solicitudes ? (clone $solicitudes)->where('estado', SolicitudEstado::Aprobada)->count() : null,
                'creditos' => $creditos?->count(),
            ],
            'pendientes' => $solicitudes ? (clone $solicitudes)
                ->where('responsable_id', $user->id)
                ->whereIn('estado', [SolicitudEstado::Borrador, SolicitudEstado::Devuelta, SolicitudEstado::EnRevision, SolicitudEstado::Aprobada])
                ->with('cliente:id,primer_nombre,apellido_paterno')
                ->latest('updated_at')->limit(5)->get(['id', 'folio', 'cliente_id', 'estado', 'updated_at'])
                ->map(fn ($solicitud) => [
                    'id' => $solicitud->id,
                    'folio' => $solicitud->folio,
                    'cliente' => trim(($solicitud->cliente?->primer_nombre ?? '').' '.($solicitud->cliente?->apellido_paterno ?? '')),
                    'estado' => $solicitud->estado->label(),
                    'actualizada' => $solicitud->updated_at?->toIso8601String(),
                ]) : [],
            'accesos' => [
                'clientes' => $puedeClientes,
                'solicitudes' => $puedeSolicitudes,
                'creditos' => $puedeCreditos,
                'productos' => Gate::allows('read productos-crediticios'),
                'plantillas' => Gate::allows('read plantillas-documentos'),
            ],
        ]);
    }
}
