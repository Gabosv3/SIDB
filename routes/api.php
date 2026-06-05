<?php

use App\Http\Controllers\Api\AsignacionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\GestionCobroController;
use App\Http\Controllers\Api\PagoVentaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API POS — Rutas públicas
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| API POS — Rutas protegidas con Sanctum
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ────────────────────────────────────────────────────────────────
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Rutas exclusivas para Vendedores y Cobradores activos ───────────────
    Route::middleware('pos.acceso')->group(function () {

        // ── Catálogos (cualquier perfil POS puede leer) ──────────────────────
        Route::get('/categorias', [CategoriaController::class, 'index']);
        Route::get('/productos', [ProductoController::class, 'index']);
        Route::get('/productos/{id}/metodos-pago', [ProductoController::class, 'paymentOptions']);
        Route::get('/productos/{id}', [ProductoController::class, 'show']);
        Route::get('/clientes', [ClienteController::class, 'index']);
        Route::get('/clientes/{id}', [ClienteController::class, 'show']);

        // ── Consulta de ventas (cualquier perfil POS puede ver) ──────────────
        Route::get('/ventas', [VentaController::class, 'index']);
        Route::get('/ventas/{id}', [VentaController::class, 'show']);
        Route::get('/ventas/{venta}/pagos', [PagoVentaController::class, 'index']);

        // ── Solo VENDEDORES: crear ventas ────────────────────────────────────
        Route::middleware('solo.vendedor')->group(function () {
            Route::post('/ventas', [VentaController::class, 'store']);

            // Consulta de asignación del día (solo lectura, el admin la crea en Filament)
            Route::get('/asignacion/hoy', [AsignacionController::class, 'hoy']);
            Route::post('/asignaciones/{id}/liquidar', [AsignacionController::class, 'liquidar']);
        });

        // ── Solo COBRADORES: registrar cobros ────────────────────────────────
        Route::middleware('solo.cobrador')->group(function () {
            Route::post('/ventas/{venta}/pagos', [PagoVentaController::class, 'store']);

            // Gestiones de cobro (cuotas)
            Route::get('/gestiones-cobro/pendientes', [GestionCobroController::class, 'pendientes']);
            Route::get('/gestiones-cobro/{id}', [GestionCobroController::class, 'show']);
            Route::post('/gestiones-cobro/{id}/pagar', [GestionCobroController::class, 'pagar']);
        });
    });
});
