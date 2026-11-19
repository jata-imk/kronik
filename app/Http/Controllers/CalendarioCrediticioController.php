<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarCalendarioCrediticioRequest;
use App\Models\CalendarioCrediticioVersion;
use App\Services\Credito\CalendarioCrediticioService;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class CalendarioCrediticioController extends Controller
{
    public function index(Request $request, CalendarioCrediticioService $calendario)
    {
        return $this->pagina($request, $calendario, null);
    }

    public function show(Request $request, CalendarioCrediticioService $calendario, CalendarioCrediticioVersion $version)
    {
        return $this->pagina($request, $calendario, $version);
    }

    public function store(GuardarCalendarioCrediticioRequest $request, CalendarioCrediticioService $calendario)
    {
        $version = $calendario->guardar(null, $request->validated(), $request->user());

        return to_route('productos-crediticios.calendario.show', $version);
    }

    public function update(GuardarCalendarioCrediticioRequest $request, CalendarioCrediticioService $calendario, CalendarioCrediticioVersion $version)
    {
        $calendario->guardar($version, $request->validated(), $request->user());

        return to_route('productos-crediticios.calendario.show', $version);
    }

    public function publicar(Request $request, CalendarioCrediticioService $calendario, CalendarioCrediticioVersion $version)
    {
        abort_unless($request->user()->is_super_admin, 403);
        $datos = $request->validate(['revision' => ['required', 'integer', 'min:1']]);
        $calendario->publicar($version, $datos['revision'], $request->user());

        return to_route('productos-crediticios.calendario.show', $version);
    }

    public function previa(\App\Http\Requests\PreviaCalendarioCrediticioRequest $request, CalendarioCrediticioVersion $version, \App\Services\Credito\PreviaCalendarioCrediticio $previa)
    {
        if ($version->revision !== (int) $request->validated('revision')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['revision' => 'El borrador cambió. Vuelve a abrir esta versión para calcular con las fechas actualizadas.']);
        }

        return response()->json($previa->calcular($version, $request->validated()));
    }

    private function pagina(Request $request, CalendarioCrediticioService $calendario, ?CalendarioCrediticioVersion $version)
    {
        $actor = $request->user();
        \Illuminate\Support\Facades\Gate::authorize(CalendarioCrediticioService::CONSULTAR);
        $edita = $actor->can(CalendarioCrediticioService::EDITAR);
        abort_if($version && ! $version->publicada_en && ! $edita, 404);
        $vigente = $calendario->vigente();

        return Inertia::render('CalendarioCrediticio/Index', [
            'version' => $version ?? $vigente,
            'vigenteId' => $vigente?->id,
            'versiones' => CalendarioCrediticioVersion::query()->when(! $edita, fn ($query) => $query->whereNotNull('publicada_en'))
                ->latest('id')->paginate(15, ['id', 'publicada_en', 'orden_publicacion']),
            'puedeEditar' => $edita,
            'puedePublicar' => (bool) $actor->is_super_admin,
            'plantilla' => $edita ? config('calendario-crediticio.cnbv_2026') : null,
            'hoy' => app(\App\Services\FechaEmpresa::class)->hoy()->toDateString(),
        ]);
    }
}
