<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CodigoPostalController;
use App\Http\Controllers\RegimenFiscalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('clientes', ClienteController::class);
    Route::get('/codigos-postales/buscar', [CodigoPostalController::class, 'buscar']);
    Route::get('/regimenes-fiscales', [RegimenFiscalController::class, 'index']);
});
