<?php

use App\Http\Controllers\Admin\MenubarItemController;
use App\Http\Controllers\Admin\EmpresaConfiguracionController;
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

    // Importante: Las rutas de clientes.historial-crediticio deben ir antes de la ruta de clientes
    Route::get('clientes/historial-crediticio', [HistorialCrediticioController::class, 'index'])->name('clientes.historial-crediticio.index');
    Route::get('clientes/{cliente}/historial-crediticio', [HistorialCrediticioController::class, 'show'])->name('clientes.historial-crediticio.show');

    Route::resource('clientes/{cliente?}/circulo-credito', CirculoCreditoController::class)->only([
        'index',
        'create',
        'store',
        'show',
    ])->names('circulo-credito');

    Route::resource('clientes', ClienteController::class)->names('clientes');

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

    Route::prefix('admin')->name('admin.')->middleware('permission:access admin')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('dashboard');

        Route::get('users/activity', [UserController::class, 'usersActivity'])
            ->middleware('permission:read activity-log')
            ->name('users.activity');
        Route::resource('users', UserController::class)
            ->middlewareFor(['index', 'show'], 'permission:read users')
            ->middlewareFor('create', 'permission:create users')
            ->middlewareFor('store', 'permission:create users')
            ->middlewareFor('edit', 'permission:update users')
            ->middlewareFor('update', 'permission:update users')
            ->middlewareFor('destroy', 'permission:delete users');
        Route::get('menubar-items/routes', [MenubarItemController::class, 'availableRoutes'])
            ->middleware('permission:read menubar-items')
            ->name('menubar-items.available-routes');
        Route::resource('menubar-items', MenubarItemController::class)
            ->middlewareFor(['index', 'show'], 'permission:read menubar-items')
            ->middlewareFor('create', 'permission:create menubar-items')
            ->middlewareFor('store', 'permission:create menubar-items')
            ->middlewareFor('edit', 'permission:update menubar-items')
            ->middlewareFor('update', 'permission:update menubar-items')
            ->middlewareFor('destroy', 'permission:delete menubar-items');
        Route::resource('roles', RoleController::class)
            ->middlewareFor(['index', 'show'], 'permission:read roles')
            ->middlewareFor('create', 'permission:create roles')
            ->middlewareFor('store', 'permission:create roles')
            ->middlewareFor('edit', 'permission:update roles')
            ->middlewareFor('update', 'permission:update roles')
            ->middlewareFor('destroy', 'permission:delete roles');
        Route::get('empresa-configuracion', [EmpresaConfiguracionController::class, 'index'])
            ->middleware('permission:read empresa-configuracion')
            ->name('empresa-configuracion.index');
        Route::put('empresa-configuracion', [EmpresaConfiguracionController::class, 'update'])
            ->middleware('permission:update empresa-configuracion')
            ->name('empresa-configuracion.update');
    });
});
