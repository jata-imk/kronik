<?php

namespace App\Http\Controllers;

use App\Enums\ClienteDocumentoTipo;
use App\Models\OriginacionPolitica;
use App\Models\ProductoVersion;
use App\Services\OriginacionPoliticaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OriginacionPoliticaController extends Controller
{
    public function show(ProductoVersion $version, OriginacionPoliticaService $service)
    {
        Gate::authorize('read productos-crediticios');
        Gate::authorize('manage origination productos-crediticios');

        return Inertia::render('ProductosCrediticios/PoliticaOriginacion', [
            'version' => $version->load('producto:id,nombre'),
            'politica' => $service->vigente($version->id),
            'historial' => OriginacionPolitica::where('producto_version_id', $version->id)->latest('numero')->paginate(10),
            'tiposDocumento' => array_map(fn ($tipo) => ['value' => $tipo->value, 'label' => ucfirst(str_replace('_', ' ', $tipo->value))], ClienteDocumentoTipo::cases()),
        ]);
    }

    public function store(Request $request, ProductoVersion $version, OriginacionPoliticaService $service)
    {
        $service->crear($version, $request->all(), $request->user());

        return back()->with('success', 'Política versionada. Las solicitudes anteriores deben reenviarse para aplicar esta versión.');
    }
}
