<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSucursalFoliosRequest;
use App\Models\Sucursal;
use App\Services\FolioService;

class SucursalFolioController extends Controller
{
    public function update(UpdateSucursalFoliosRequest $request, Sucursal $sucursal, FolioService $service)
    {
        $service->configurar($sucursal, $request->validated(), $request->user());

        return back()->with('success', 'Numeración actualizada. Los folios anteriores conservan su valor.');
    }
}
