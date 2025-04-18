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
            'codigo_postal' => 'required|string|min:3|max:15',
        ]);

        return CodigoPostalResource::collection(
            $this->codigoPostalService->buscarPorCodigo($request->codigo_postal)
        );
    }
}
