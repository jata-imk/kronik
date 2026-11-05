<?php

namespace App\Http\Controllers;

use App\Enums\ClienteDocumentoTipo;
use App\Http\Requests\GuardarOriginacionPoliticaRequest;
use App\Models\OriginacionPolitica;
use App\Models\ProductoVersion;
use App\Services\OriginacionPoliticaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OriginacionPoliticaController extends Controller
{
    public function show(Request $request, ProductoVersion $version, OriginacionPoliticaService $service)
    {
        Gate::authorize('read productos-crediticios');
        $request->validate(['politica_id' => 'nullable|integer|min:1', 'modo' => ['nullable', \Illuminate\Validation\Rule::in(['consulta', 'nueva'])]]);
        $actual = $service->vigente($version->id);
        $seleccionada = $request->filled('politica_id')
            ? OriginacionPolitica::where('producto_version_id', $version->id)->findOrFail($request->integer('politica_id')) : $actual;
        $puedeCrear = Gate::allows('manage origination productos-crediticios') && $version->estado !== \App\Enums\ProductoVersionEstado::Retirada;

        return Inertia::render('ProductosCrediticios/PoliticaOriginacion', [
            'version' => $version->load('producto:id,nombre'),
            'politica' => $actual,
            'politicaSeleccionada' => $seleccionada,
            'puedeCrear' => $puedeCrear,
            'modoPolitica' => $puedeCrear && $request->input('modo') === 'nueva' ? 'nueva' : 'consulta',
            'historial' => OriginacionPolitica::where('producto_version_id', $version->id)->latest('numero')->paginate(10)->withQueryString(),
            'tiposDocumento' => array_map(fn ($tipo) => ['value' => $tipo->value, 'label' => ucfirst(str_replace('_', ' ', $tipo->value))], ClienteDocumentoTipo::cases()),
        ]);
    }

    public function store(GuardarOriginacionPoliticaRequest $request, ProductoVersion $version, OriginacionPoliticaService $service)
    {
        $politica = $service->crear($version, $request->validated(), $request->user());

        return redirect()->route('originacion-politicas.show', ['version' => $version->id, 'politica_id' => $politica->id])
            ->with('success', 'Política versionada. Las solicitudes anteriores deben reenviarse para aplicar esta versión.');
    }
}
