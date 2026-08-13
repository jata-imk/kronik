<?php

namespace App\Http\Controllers;

use App\Models\ConceptoComision;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class ConceptoComisionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:manage commissions productos-crediticios')];
    }

    public function store(Request $request)
    {
        ConceptoComision::create($this->validateConcepto($request));

        return back()->with('success', 'Concepto de comisión creado.');
    }

    public function update(Request $request, ConceptoComision $concepto)
    {
        $concepto->update($this->validateConcepto($request, $concepto));

        return back()->with('success', 'Concepto de comisión actualizado.');
    }

    public function destroy(ConceptoComision $concepto)
    {
        $concepto->update(['activo' => false, 'retirado_desde' => today()]);

        return back()->with('success', 'Concepto retirado; las versiones históricas lo conservan.');
    }

    private function validateConcepto(Request $request, ?ConceptoComision $concepto = null): array
    {
        return $request->validate([
            'clave' => ['required', 'string', 'max:60', Rule::unique('conceptos_comision', 'clave')->ignore($concepto)],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'referencia_reco' => ['nullable', 'string', 'max:255'],
            'es_oficial_reco' => ['required', 'boolean'],
            'revisado' => ['required', 'boolean'],
            'activo' => ['required', 'boolean'],
        ], ['clave.unique' => 'La clave del concepto de comisión ya está en uso.'], ['clave' => 'clave', 'nombre' => 'nombre']);
    }
}
