<?php

use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\AsignacionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CobroController;
use App\Http\Controllers\HikvisionAsistenciaWebhookController;
use App\Http\Controllers\Api\PagareController;
use App\Http\Controllers\Api\PagoVentaController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\PreventaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\PushTokenController;
use App\Http\Controllers\Api\ReintegroController;
use App\Http\Controllers\Api\GarantiaController;
use App\Http\Controllers\Api\ValeController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\MetaWhatsAppWebhookController;
use App\Http\Controllers\YCloudWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API POS — Rutas públicas
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/version', [AppVersionController::class, 'actual']);

// Webhook de YCloud (WhatsApp Coexistence) — sin auth Sanctum, se verifica
// por firma HMAC propia (ver YCloudWebhookController::firmaValida).
Route::post('/webhooks/ycloud/whatsapp', [YCloudWebhookController::class, 'receive']);

// Webhook directo de Meta Cloud API (conexión propia, sin BSP intermediario).
// GET = verificación única al configurar; POST = eventos reales.
Route::get('/webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'verificar']);
Route::post('/webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'recibir']);

Route::post('/webhooks/hikvision/asistencia/{token}', [HikvisionAsistenciaWebhookController::class, 'recibir']);

/*
|--------------------------------------------------------------------------
| API POS — Rutas protegidas con Sanctum
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ────────────────────────────────────────────────────────────────
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Push notifications ───────────────────────────────────────────────────
    Route::post('/push-tokens', [PushTokenController::class, 'store']);
    Route::delete('/push-tokens', [PushTokenController::class, 'destroy']);

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
        Route::post('/clientes/{id}/vincular', [ClienteController::class, 'vincular']);
        Route::get('/clientes/{id}/grupo', [ClienteController::class, 'grupo']);
        Route::post('/clientes/{id}/desvincular', [ClienteController::class, 'desvincular']);
        Route::get('/clientes/{id}', [ClienteController::class, 'show']);

        // ── Consulta de ventas (solo del propio usuario) ──────────────────────
        Route::get('/ventas', [VentaController::class, 'index']);
        Route::get('/ventas/{id}', [VentaController::class, 'show']);
        Route::get('/ventas/{venta}/pagos', [PagoVentaController::class, 'index']);
        Route::post('/ventas/{venta}/pagos', [PagoVentaController::class, 'store']);

        // ── Solo VENDEDORES: crear ventas ────────────────────────────────────
        Route::middleware('solo.vendedor')->group(function () {
            Route::post('/ventas', [VentaController::class, 'store']);
            Route::post('/ventas/{id}/anular', [VentaController::class, 'anular']);
            Route::post('/pagares', [PagareController::class, 'store']);
            Route::patch('/pagares/{id}/venta', [PagareController::class, 'vincularVenta']);

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

        // ── Garantías (vendedores y cobradores con acceso POS) ───────────────
        Route::prefix('garantias')->group(function () {
            Route::get('/', [GarantiaController::class, 'index']);
            Route::post('/', [GarantiaController::class, 'store']);
        });

        // ── Cobros: reportes accesibles por cualquier perfil POS ─────────────
        Route::prefix('cobros')->group(function () {
            Route::get('/resumen-dia', [CobroController::class, 'resumenDia']);
        });

        // ── Vales (consumo/vehículo) y vehículos: cualquier perfil POS ────────
        Route::get('/vehiculos/disponibles', [VehiculoController::class, 'disponibles']);
        Route::get('/vales', [ValeController::class, 'index']);
        Route::post('/vales', [ValeController::class, 'store']);

        // ── Preventas: cobrador registra, vendedor asignado consulta ─────────
        Route::prefix('preventas')->group(function () {
            Route::get('/', [PreventaController::class, 'index']);
            Route::middleware('solo.cobrador')->post('/', [PreventaController::class, 'store']);
        });

        // ── Solo COBRADORES: módulo de cobros ────────────────────────────────
        Route::middleware('solo.cobrador')->prefix('cobros')->group(function () {
            // Ruta del día
            Route::get('/ruta-hoy', [CobroController::class, 'rutaHoy']);

            // Historial de cobros por día (propio) — ?fecha=YYYY-MM-DD, mes en curso
            Route::get('/historial', [CobroController::class, 'historial']);

            // Directorio de TODOS los clientes del cobrador, agrupados por ruta
            Route::get('/clientes', [CobroController::class, 'todosLosClientes']);

            // Clientes por ruta
            Route::get('/rutas/{ruta_id}/clientes', [CobroController::class, 'clientesPorRuta']);

            // Orden de visita guardado en el servidor (sobrevive reinstalaciones)
            Route::get('/rutas/{ruta_id}/orden', [CobroController::class, 'ordenClientes']);
            Route::post('/rutas/{ruta_id}/orden', [CobroController::class, 'actualizarOrden']);

            // Buscar cliente por código (entre TODOS los clientes del cobrador, sin importar el día/ruta)
            Route::get('/clientes/buscar', [CobroController::class, 'buscarCliente']);

            // Detalle e historial del cliente
            Route::get('/clientes/{id}', [CobroController::class, 'detalleCliente']);
            Route::get('/clientes/{id}/gestiones-pendientes', [CobroController::class, 'gestionesPendientes']);

            // Perfil completo de solo lectura (mismo detalle que ve el admin en el
            // panel web) — cubre todas las rutas del cobrador, no solo la de hoy.
            Route::get('/clientes/{id}/perfil', [CobroController::class, 'perfilCliente']);

            // Registrar pago: directo al cliente (aplica a cuota más antigua)
            Route::post('/clientes/{id}/pagar', [CobroController::class, 'pagarCliente']);

            // Registrar pago: a una gestión específica
            Route::post('/gestiones/{id}/pagar', [CobroController::class, 'pagar']);

            // Registrar visita sin pago (con foto opcional)
            Route::post('/clientes/{id}/visita', [CobroController::class, 'registrarVisita']);

            // Desempeño del propio cobrador (cobrado esta semana/mes vs período
            // anterior, % de ruta gestionada hoy, cuentas en mora bajo su cargo)
            Route::get('/desempeno', [CobroController::class, 'desempeno']);

            // ── Supervisor: siempre puede cobrar (rutas mezcladas arriba vía
            // rutasIdsAccesibles), más lo exclusivo de su rol ──────────────────
            Route::prefix('supervisor')->group(function () {
                Route::get('/rutas', [CobroController::class, 'misRutasSupervisadas']);
                Route::get('/rutas/{ruta_id}/historial', [CobroController::class, 'historialRutaSupervisada']);
                Route::get('/desempeno-cobradores', [CobroController::class, 'desempenoCobradores']);
            });
            Route::post('/supervisiones', [CobroController::class, 'registrarSupervision']);
            Route::get('/supervisiones', [CobroController::class, 'misSupervisiones']);

            // Encuesta de control y verificación al cliente (contrasta lo que
            // dice el cliente contra lo que el sistema tiene registrado)
            Route::post('/encuestas-cliente', [CobroController::class, 'registrarEncuestaCliente']);
            Route::get('/encuestas-cliente', [CobroController::class, 'misEncuestasCliente']);
        });
    });
});

