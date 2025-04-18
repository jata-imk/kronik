<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegimenFiscalResource;
use App\Models\RegimenFiscal;
use Illuminate\Http\Request;

class RegimenFiscalController extends Controller
{
    public function index()
    {
        return RegimenFiscalResource::collection(RegimenFiscal::all());
    }
}
