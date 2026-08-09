<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrentSucursalController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
        ], [], [
            'sucursal_id' => 'sucursal',
        ]);

        $sucursal = Sucursal::findOrFail($validated['sucursal_id']);

        abort_unless($request->user()->switchSucursal($sucursal), 403, 'No tienes acceso a la sucursal seleccionada.');

        return back(303);
    }
}
