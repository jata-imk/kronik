<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Services\ClienteService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request, ClienteService $clienteService)
    {
        return ClienteResource::collection($clienteService->readAll());
    }

    public function store(StoreClienteRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $cliente = Cliente::create($request->validated());

            $cliente->datosFiscales()->create($request->validated()['datos_fiscales']);

            foreach ($request->validated()['direcciones'] as $direccion) {
                $cliente->direcciones()->create($direccion);
            }

            return response()->json($cliente->load(['datosFiscales', 'direcciones']), 201);
        });
    }

    public function show(Cliente $cliente)
    {
        return $cliente->load(['datosFiscales', 'direcciones']);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        return DB::transaction(function () use ($request, $cliente) {
            $cliente->update($request->validated());

            if ($request->has('datos_fiscales')) {
                $cliente->datosFiscales()->updateOrCreate([], $request->validated()['datos_fiscales']);
            }

            if ($request->has('direcciones')) {

                foreach ($request->validated()['direcciones'] as $direccion) {
                    $cliente->direcciones()->updateOrCreate(['tipo' => $direccion['tipo']], $direccion);
                }
            }

            return response()->json($cliente->load(['datosFiscales', 'direcciones']));
        });
    }

    public function destroy(Cliente $cliente)
    {
        return DB::transaction(function () use ($cliente) {
            $cliente->direcciones()->delete();
            $cliente->datosFiscales()->delete();
            $cliente->delete();

            return response()->json(null, 204);
        });
    }
}
