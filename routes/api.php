<?php

use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\AsignacionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CobroController;
use App\Http\Controllers\Api\PagoVentaController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ReintegroController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API POS — Rutas públicas
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::get('/version', [AppVersionController::class, 'actual']);

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

        // ── POS: heartbeat de estado del dispositivo ────────────────────────
        Route::post('/pos/heartbeat', [PosController::class, 'heartbeat']);

        // ── Catálogos (cualquier perfil POS puede leer) ──────────────────────
        Route::get('/categorias', [CategoriaController::class, 'index']);
        Route::get('/productos', [ProductoController::class, 'index']);
        Route::get('/productos/{id}/metodos-pago', [ProductoController::class, 'paymentOptions']);
        Route::get('/productos/{id}', [ProductoController::class, 'show']);
        Route::get('/clientes', [ClienteController::class, 'index']);
        Route::post('/clientes', [ClienteController::class, 'store']);
        Route::patch('/clientes/{id}/ubicacion', [ClienteController::class, 'actualizarUbicacion']);
        Route::patch('/clientes/{id}/telefonos', [ClienteController::class, 'actualizarTelefonos']);
        Route::patch('/clientes/{id}/nombre', [ClienteController::class, 'actualizarNombre']);
        Route::get('/clientes/{id}', [ClienteController::class, 'show']);

        // ── Consulta de ventas (solo del propio usuario) ──────────────────────
        Route::get('/ventas', [VentaController::class, 'index']);
        Route::get('/ventas/{id}', [VentaController::class, 'show']);

        // ── Solo VENDEDORES: crear ventas ────────────────────────────────────
        Route::middleware('solo.vendedor')->group(function () {
            Route::post('/ventas', [VentaController::class, 'store']);

            // Consulta de asignación del día (solo lectura, el admin la crea en Filament)
            Route::get('/asignacion/hoy', [AsignacionController::class, 'hoy']);
            Route::post('/asignaciones/{id}/liquidar', [AsignacionController::class, 'liquidar']);
        });

        // ── Reintegros (vendedores y cobradores con acceso POS) ──────────────
        Route::prefix('reintegros')->group(function () {
            Route::get('/candidatos', [ReintegroController::class, 'candidatos']);
            Route::get('/vendedores', [ReintegroController::class, 'vendedores']);
            Route::get('/', [ReintegroController::class, 'index']);
            Route::post('/', [ReintegroController::class, 'store']);
            Route::patch('/{id}/estado', [ReintegroController::class, 'actualizarEstado']);
        });

        // ── Cobros: reportes accesibles por cualquier perfil POS ─────────────
        Route::prefix('cobros')->group(function () {
            Route::get('/resumen-dia', [CobroController::class, 'resumenDia']);
        });

        // ── Solo COBRADORES: módulo de cobros ────────────────────────────────
        Route::middleware('solo.cobrador')->prefix('cobros')->group(function () {
            // Ruta del día
            Route::get('/ruta-hoy', [CobroController::class, 'rutaHoy']);

            // Clientes por ruta
            Route::get('/rutas/{ruta_id}/clientes', [CobroController::class, 'clientesPorRuta']);

            // Orden de visita guardado en el servidor (sobrevive reinstalaciones)
            Route::get('/rutas/{ruta_id}/orden', [CobroController::class, 'ordenClientes']);
            Route::post('/rutas/{ruta_id}/orden', [CobroController::class, 'actualizarOrden']);

            // Detalle e historial del cliente
            Route::get('/clientes/{id}', [CobroController::class, 'detalleCliente']);
            Route::get('/clientes/{id}/gestiones-pendientes', [CobroController::class, 'gestionesPendientes']);

            // Registrar pago: directo al cliente (aplica a cuota más antigua)
            Route::post('/clientes/{id}/pagar', [CobroController::class, 'pagarCliente']);

            // Registrar pago: a una gestión específica
            Route::post('/gestiones/{id}/pagar', [CobroController::class, 'pagar']);

            // Registrar visita sin pago (con foto opcional)
            Route::post('/clientes/{id}/visita', [CobroController::class, 'registrarVisita']);
        });
    });
});
