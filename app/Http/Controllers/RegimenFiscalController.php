<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegimenFiscalResource;
use App\Services\RegimenFiscalService;
use Illuminate\Http\Request;

class RegimenFiscalController extends Controller
{
    public function index(Request $request, RegimenFiscalService $regimenFiscalService)
    {
        return RegimenFiscalResource::collection($regimenFiscalService->readAll());
    }
}
