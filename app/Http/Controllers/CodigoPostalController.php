<?php

namespace App\Http\Controllers;

use App\Http\Resources\CodigoPostalResource;
use App\Services\CodigoPostalService;
use Illuminate\Http\Request;

class CodigoPostalController extends Controller
{
    private $codigoPostalService;

    public function __construct(CodigoPostalService $codigoPostalService)
    {
        $this->codigoPostalService = $codigoPostalService;
    }
    public function buscar(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|min:3|max:15',
        ]);

        return CodigoPostalResource::collection(
            $this->codigoPostalService->buscarPorCodigo($request->codigo)
        );
    }

    public function sugerencias(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|min:2|max:15',
        ]);

        return [
            'data' => $this->codigoPostalService->sugerencias($request->codigo),
        ];
    }
}
