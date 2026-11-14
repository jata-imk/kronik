<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompletarCapturaHistoricaRequest;
use App\Models\Credito;
use App\Services\Credito\CapturaHistoricaService;

class CreditoCapturaHistoricaController extends Controller
{
    public function store(CompletarCapturaHistoricaRequest $request, Credito $credito, CapturaHistoricaService $service)
    {
        $service->completar($credito, $request->user());

        return redirect()->route('creditos.show', $credito)->with('success', 'Captura histórica completada. El saldo se consulta con todos los movimientos registrados; no se enviaron avisos ni se movió dinero.');
    }
}
