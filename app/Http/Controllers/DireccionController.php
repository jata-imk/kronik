<?php

namespace App\Http\Controllers;

use App\Http\Resources\CodigoPostalResource;
use App\Services\DireccionService;
use Illuminate\Http\Request;

class DireccionController extends Controller
{
    private $direccionService;

    public function __construct(DireccionService $direccionService)
    {
        $this->direccionService = $direccionService;
    }
    public function autocompletarPorCodigoPostal(Request $request)
    {
        $request->validate([
            'codigo_postal' => 'required|string|min:3|max:15',
        ]);

        return response()->json(
            $this->direccionService->obtenerDatosPorCodigoPostal($request->codigo_postal)->map(function ($cp) {
                return new CodigoPostalResource($cp);
            })

        );
    }
}
