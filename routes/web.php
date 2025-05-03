<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CodigoPostalController;
use App\Http\Controllers\GeocodingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::resource('clientes', ClienteController::class);

    Route::get('/codigos-postales/sugerencias', [CodigoPostalController::class, 'sugerencias']);
    Route::get('/codigos-postales/buscar', [CodigoPostalController::class, 'buscar']);

    Route::get('/sakai', function () {
        return Inertia::render('Sakai');
    })->name('sakai');

    Route::get('/componentes', function () {
        return Inertia::render('Componentes');
    })->name('componentes');

    Route::get('/geocoding/search', [GeocodingController::class, 'search'])->name('geocoding.search');
});
