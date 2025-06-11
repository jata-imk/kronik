<?php

use App\Http\Controllers\Admin\MenubarItemController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CirculoCreditoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CodigoPostalController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\HistorialCrediticioController;
use App\Http\Controllers\TeamController;
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

    Route::resource('clientes', ClienteController::class)->names('clientes');

    Route::get('/historial-crediticio', [HistorialCrediticioController::class, 'index'])->name('historial-crediticio.index');
    Route::resource('circulo-credito', CirculoCreditoController::class)->only([
        'index',
        'create',
        'store',
        'show'
    ])->names('circulo-credito');

    Route::get('/codigos-postales/sugerencias', [CodigoPostalController::class, 'sugerencias'])->name('codigos-postales.sugerencias');
    Route::get('/codigos-postales/buscar', [CodigoPostalController::class, 'buscar'])->name('codigos-postales.buscar');
    Route::get('/geocoding/search', [GeocodingController::class, 'search'])->name('geocoding.search');

    Route::get('/sakai', function () {
        return Inertia::render('Sakai');
    })->name('sakai');

    Route::get('/componentes', function () {
        return Inertia::render('Componentes');
    })->name('componentes');

    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('dashboard');

        Route::resource('users', UserController::class);
        Route::resource('menubar-items', MenubarItemController::class);
        Route::resource('roles', RoleController::class);
    });
});
