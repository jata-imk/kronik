<?php

use App\Http\Controllers\Admin\EmpresaConfiguracionController;
use App\Http\Controllers\Admin\MenubarItemController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SucursalController;
use App\Http\Controllers\Admin\TeamController as AdminTeamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CirculoCreditoController;
use App\Http\Controllers\ClienteConsentimientoSicController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ClienteDocumentoController;
use App\Http\Controllers\ClienteExpedienteController;
use App\Http\Controllers\ClienteGarantiaController;
use App\Http\Controllers\ClienteReferenciaController;
use App\Http\Controllers\ClienteVinculoController;
use App\Http\Controllers\CodigoPostalController;
use App\Http\Controllers\ConceptoComisionController;
use App\Http\Controllers\CurrentSucursalController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\HistorialCrediticioController;
use App\Http\Controllers\ProductoCrediticioController;
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
    'active',
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

    Route::get('clientes/{cliente}/expediente', [ClienteExpedienteController::class, 'show'])->name('clientes.expediente.show');
    Route::patch('clientes/{cliente}/expediente/perfil', [ClienteExpedienteController::class, 'updateProfile'])->name('clientes.expediente.perfil.update');

    Route::post('clientes/{cliente}/documentos', [ClienteDocumentoController::class, 'store'])->name('clientes.documentos.store');
    Route::patch('clientes/{cliente}/documentos/{documento}/estado', [ClienteDocumentoController::class, 'updateStatus'])->name('clientes.documentos.estado.update');
    Route::get('clientes/{cliente}/documentos/{documento}/descargar', [ClienteDocumentoController::class, 'download'])->name('clientes.documentos.download');

    Route::post('clientes/{cliente}/referencias', [ClienteReferenciaController::class, 'store'])->name('clientes.referencias.store');
    Route::put('clientes/{cliente}/referencias/{referencia}', [ClienteReferenciaController::class, 'update'])->name('clientes.referencias.update');
    Route::delete('clientes/{cliente}/referencias/{referencia}', [ClienteReferenciaController::class, 'destroy'])->name('clientes.referencias.destroy');

    Route::post('clientes/{cliente}/vinculos', [ClienteVinculoController::class, 'store'])->name('clientes.vinculos.store');
    Route::delete('clientes/{cliente}/vinculos/{vinculo}', [ClienteVinculoController::class, 'destroy'])->name('clientes.vinculos.destroy');

    Route::post('clientes/{cliente}/garantias', [ClienteGarantiaController::class, 'store'])->name('clientes.garantias.store');
    Route::put('clientes/{cliente}/garantias/{garantia}', [ClienteGarantiaController::class, 'update'])->name('clientes.garantias.update');
    Route::delete('clientes/{cliente}/garantias/{garantia}', [ClienteGarantiaController::class, 'destroy'])->name('clientes.garantias.destroy');

    Route::post('clientes/{cliente}/consentimientos-sic', [ClienteConsentimientoSicController::class, 'store'])->name('clientes.consentimientos-sic.store');
    Route::patch('clientes/{cliente}/consentimientos-sic/{consentimiento}/revocar', [ClienteConsentimientoSicController::class, 'revoke'])->name('clientes.consentimientos-sic.revoke');
    Route::get('clientes/{cliente}/consentimientos-sic/{consentimiento}/evidencia', [ClienteConsentimientoSicController::class, 'download'])->name('clientes.consentimientos-sic.download');
    Route::patch('clientes/{cliente}/sucursal', [ClienteController::class, 'transfer'])->name('clientes.sucursal.transfer');

    Route::resource('clientes', ClienteController::class)->names('clientes');

    Route::get('productos-crediticios', [ProductoCrediticioController::class, 'index'])->name('productos-crediticios.index');
    Route::post('productos-crediticios', [ProductoCrediticioController::class, 'store'])->name('productos-crediticios.store');
    Route::put('productos-crediticios/{producto}/versiones/{version}', [ProductoCrediticioController::class, 'update'])->name('productos-crediticios.update');
    Route::post('productos-crediticios/{producto}/versiones/{version}/duplicar', [ProductoCrediticioController::class, 'versionar'])->name('productos-crediticios.versionar');
    Route::post('producto-versiones/{version}/activar', [ProductoCrediticioController::class, 'activar'])->name('productos-crediticios.activar');
    Route::post('producto-versiones/{version}/retirar', [ProductoCrediticioController::class, 'retirar'])->name('productos-crediticios.retirar');
    Route::post('producto-versiones/{version}/simular', [ProductoCrediticioController::class, 'simular'])->name('productos-crediticios.simular');
    Route::post('conceptos-comision', [ConceptoComisionController::class, 'store'])->name('conceptos-comision.store');
    Route::put('conceptos-comision/{concepto}', [ConceptoComisionController::class, 'update'])->name('conceptos-comision.update');
    Route::delete('conceptos-comision/{concepto}', [ConceptoComisionController::class, 'destroy'])->name('conceptos-comision.destroy');

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
    Route::put('/current-sucursal', [CurrentSucursalController::class, 'update'])->name('current-sucursal.update');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Dashboard');
        })->middleware('permission:access admin')->name('dashboard');

        Route::get('users/activity', [UserController::class, 'usersActivity'])->name('users.activity');
        Route::get('users/activity/export', [UserController::class, 'exportActivity'])->name('users.activity.export');
        Route::post('users/{user}/invitation', [UserController::class, 'resendInvitation'])->name('users.invitation.resend');
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('configuracion-empresa', [EmpresaConfiguracionController::class, 'index'])->name('configuracion-empresa.index');
        Route::put('configuracion-empresa', [EmpresaConfiguracionController::class, 'update'])->name('configuracion-empresa.update');
        Route::resource('sucursales', SucursalController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['sucursales' => 'sucursal']);
        Route::resource('teams', AdminTeamController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('menubar-items/routes', [MenubarItemController::class, 'availableRoutes'])->name('menubar-items.available-routes');
        Route::resource('menubar-items', MenubarItemController::class);
        Route::resource('roles', RoleController::class);
    });
});
