<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DireccionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('clientes', ClienteController::class)->middleware('auth:sanctum');
Route::get('/direccion/autocompletar', [DireccionController::class, 'autocompletarPorCodigoPostal'])->middleware('auth:sanctum');
