<?php

use App\Models\RutaCobro;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/administrativo');
});

// Fallback para que el middleware 'auth' pueda redirigir (route('login')) en rutas
// fuera de Filament; sin esto, un acceso sin sesión lanza RouteNotFoundException (500).
Route::get('/login', fn () => redirect()->route('filament.administrativo.auth.login'))->name('login');

// Exportación de clientes
Route::middleware(['web', 'auth', 'can:Export:Clientes'])->prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/exportar/csv', 'App\Http\Controllers\ClienteExportController@exportarSimpleCSV')->name('exportar.csv');
});

// Pagos y Ubicación (masivo)
Route::middleware(['web', 'auth', 'can:View:PagosUbicacion'])->prefix('pagos-ubicacion')->name('pagos-ubicacion.')->group(function () {
    Route::get('/', 'App\Http\Controllers\PagosYUbicacionController@index')->name('index');
    Route::post('/procesar', 'App\Http\Controllers\PagosYUbicacionController@procesar')->name('procesar');
});

// Asignaciones diarias (rutas protegidas por autenticación)
Route::middleware(['web', 'auth', 'can:View:AsignacionesDiarias'])->prefix('asignaciones-diarias')->name('asignacion-diaria.')->group(function () {
    Route::get('{tenant}/crear', 'App\Http\Controllers\AsignacionDiariaController@crear')
        ->name('crear')
        ->where('tenant', '[0-9]+');
    Route::post('{tenant}/guardar', 'App\Http\Controllers\AsignacionDiariaController@guardar')
        ->name('guardar')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/{asignacion}/editar', 'App\Http\Controllers\AsignacionDiariaController@editar')
        ->name('editar')
        ->where(['tenant' => '[0-9]+', 'asignacion' => '[0-9]+']);
    Route::post('{tenant}/{asignacion}/actualizar', 'App\Http\Controllers\AsignacionDiariaController@actualizar')
        ->name('actualizar')
        ->where(['tenant' => '[0-9]+', 'asignacion' => '[0-9]+']);
    Route::get('{tenant}/{asignacion}/corte', 'App\Http\Controllers\CorteInventarioController@mostrar')
        ->name('corte')
        ->where(['tenant' => '[0-9]+', 'asignacion' => '[0-9]+']);
    Route::get('{tenant}/{asignacion}/corte/pdf', 'App\Http\Controllers\CorteInventarioController@generarPdf')
        ->name('corte.pdf')
        ->where(['tenant' => '[0-9]+', 'asignacion' => '[0-9]+']);
});

// Reportes
Route::middleware(['web', 'auth', 'can:View:Reportes'])->prefix('reportes')->name('reporte.')->group(function () {
    Route::get('diario/{tenant}', 'App\Http\Controllers\ReporteController@reporteDiario')
        ->name('diario')
        ->where('tenant', '[0-9]+');
    Route::get('vendedor/{tenant}/{vendedor}', 'App\Http\Controllers\ReporteController@reporteVendedor')
        ->name('vendedor')
        ->where(['tenant' => '[0-9]+', 'vendedor' => '[0-9]+']);
    Route::get('liquidados/{tenant}', 'App\Http\Controllers\ReporteController@reporteLiquidados')
        ->name('liquidados')
        ->where('tenant', '[0-9]+');
});

// ─────────────────────────────────────────────────────────────────────────────
// Monitor POS
Route::middleware(['web', 'auth'])->prefix('pos')->name('pos.')->group(function () {
    Route::middleware('can:View:MonitorPos')->group(function () {
        Route::get('{tenant}/monitor', 'App\Http\Controllers\PosMonitorController@index')
            ->name('monitor')
            ->where('tenant', '[0-9]+');
        Route::get('{tenant}/monitor/data', 'App\Http\Controllers\PosMonitorController@data')
            ->name('monitor.data')
            ->where('tenant', '[0-9]+');
    });
    Route::get('{tenant}/resumen', 'App\Http\Controllers\PosMonitorController@resumen')
        ->middleware('can:View:ResumenDia')
        ->name('resumen')
        ->where('tenant', '[0-9]+');
});

// ─────────────────────────────────────────────────────────────────────────────
// Clientes por Ruta (orden de visita y revisión de asignación)
Route::middleware(['web', 'auth', 'can:View:ClientesRuta'])->prefix('clientes-ruta')->name('clientes-ruta.')->group(function () {
    Route::get('{tenant}', 'App\Http\Controllers\ClientesRutaController@index')
        ->name('index')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/data', 'App\Http\Controllers\ClientesRutaController@data')
        ->name('data')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/clientes/{cliente}/detalle', 'App\Http\Controllers\ClientesRutaController@detalleCliente')
        ->name('detalle')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/reordenar', 'App\Http\Controllers\ClientesRutaController@reordenar')
        ->name('reordenar')
        ->where('tenant', '[0-9]+');
    Route::post('{tenant}/clientes/{cliente}/ruta', 'App\Http\Controllers\ClientesRutaController@cambiarRuta')
        ->name('cambiar-ruta')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/clientes/{cliente}/abono-inicial', 'App\Http\Controllers\ClientesRutaController@actualizarAbonoInicial')
        ->name('abono-inicial')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/clientes/{cliente}/precio-venta', 'App\Http\Controllers\ClientesRutaController@actualizarPrecioVenta')
        ->name('precio-venta')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/clientes/{cliente}/pago-fecha', 'App\Http\Controllers\ClientesRutaController@actualizarPagoFecha')
        ->name('pago-fecha')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/clientes/{cliente}/campo', 'App\Http\Controllers\ClientesRutaController@actualizarCampo')
        ->name('campo')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::delete('{tenant}/clientes/{cliente}', 'App\Http\Controllers\ClientesRutaController@eliminarCliente')
        ->name('eliminar-cliente')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/clientes', 'App\Http\Controllers\ClientesRutaController@crearCliente')
        ->name('crear-cliente')
        ->where('tenant', '[0-9]+');
    Route::post('{tenant}/importar/preview', 'App\Http\Controllers\ClientesRutaController@previewExcel')
        ->name('importar.preview')
        ->where('tenant', '[0-9]+');
    Route::post('{tenant}/importar/procesar', 'App\Http\Controllers\ClientesRutaController@procesarExcel')
        ->name('importar.procesar')
        ->where('tenant', '[0-9]+');
});

// ─────────────────────────────────────────────────────────────────────────────
// Perfil de Empleado
Route::middleware(['web', 'auth', 'can:View:PerfilEmpleado'])->prefix('empleados')->name('empleados.')->group(function () {
    Route::get('{tenant}/{user}', 'App\Http\Controllers\EmpleadoPerfilController@show')
        ->name('show')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
    Route::post('{tenant}/{user}/personal', 'App\Http\Controllers\EmpleadoPerfilController@actualizarPersonal')
        ->name('actualizarPersonal')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
    Route::post('{tenant}/{user}/laboral', 'App\Http\Controllers\EmpleadoPerfilController@actualizarLaboral')
        ->name('actualizarLaboral')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
    Route::post('{tenant}/{user}/bloquear', 'App\Http\Controllers\EmpleadoPerfilController@toggleBloqueo')
        ->name('toggleBloqueo')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
    Route::post('{tenant}/{user}/resetear-password', 'App\Http\Controllers\EmpleadoPerfilController@resetearPassword')
        ->name('resetearPassword')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
    Route::post('{tenant}/{user}/cerrar-sesiones', 'App\Http\Controllers\EmpleadoPerfilController@cerrarSesiones')
        ->name('cerrarSesiones')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
    Route::post('{tenant}/{user}/documentos', 'App\Http\Controllers\EmpleadoPerfilController@subirDocumento')
        ->name('subirDocumento')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
    Route::post('{tenant}/{user}/documentos/{documento}/verificar', 'App\Http\Controllers\EmpleadoPerfilController@verificarDocumento')
        ->name('verificarDocumento')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+', 'documento' => '[0-9]+']);
    Route::delete('{tenant}/{user}/documentos/{documento}', 'App\Http\Controllers\EmpleadoPerfilController@eliminarDocumento')
        ->name('eliminarDocumento')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+', 'documento' => '[0-9]+']);
    Route::get('{tenant}/{user}/expediente', 'App\Http\Controllers\EmpleadoPerfilController@descargarExpediente')
        ->name('descargarExpediente')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
});

// WhatsApp Center
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['web', 'auth'])->prefix('whatsapp')->name('whatsapp.')->group(function () {

    // ── Centro principal (SOLO LECTURA - Sin envío de mensajes para evitar costos) ──
    Route::middleware('can:View:WhatsappCenter')->group(function () {
        Route::get('{tenant}', 'App\Http\Controllers\WhatsappController@index')
            ->name('index')->where('tenant', '[0-9]+');
        Route::get('{tenant}/conversation/{conversation}', 'App\Http\Controllers\WhatsappController@show')
            ->name('show')->where(['tenant' => '[0-9]+', 'conversation' => '[0-9]+']);
        // DESHABILITADO: Envío de mensajes (costo en Meta Cloud API)
        // Route::post('{tenant}/send', 'App\Http\Controllers\WhatsappController@send')
        //     ->name('send')->where('tenant', '[0-9]+');
        // DESHABILITADO: Recordatorios automáticos (costo en Meta Cloud API)
        // Route::post('{tenant}/reminder/{cliente}', 'App\Http\Controllers\WhatsappController@sendReminder')
        //     ->name('sendReminder')->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
        // DESHABILITADO: Apertura de cliente (no necesario sin envío)
        // Route::get('{tenant}/open/{cliente}', 'App\Http\Controllers\WhatsappController@openClient')
        //     ->name('openClient')->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
        // DESHABILITADO: Mensajes rápidos (costo en Meta Cloud API)
        // Route::post('{tenant}/quick-message', 'App\Http\Controllers\WhatsappController@quickMessage')
        //     ->name('quickMessage')->where('tenant', '[0-9]+');
    });

    // ── DESHABILITADO: Plantillas y automatizaciones (generaban costos en Meta Cloud API) ──
    // Las siguientes rutas se comentaron para evitar envío de mensajes:
    // - Gestión de plantillas
    // - Configuración de automatizaciones
    // - Test de envío de mensajes
    /*
    Route::middleware('can:Manage:WhatsappSettings')->group(function () {
        // ── Cuentas (números conectados) ──────────────────────────────────────
        Route::prefix('{tenant}/accounts')->name('accounts.')->where(['tenant' => '[0-9]+'])->group(function () {
            Route::get('/', 'App\Http\Controllers\WhatsappAccountController@index')->name('index');
            Route::get('/create', 'App\Http\Controllers\WhatsappAccountController@create')->name('create');
            Route::post('/', 'App\Http\Controllers\WhatsappAccountController@store')->name('store');
            Route::get('/{account}/edit', 'App\Http\Controllers\WhatsappAccountController@edit')->name('edit');
            Route::put('/{account}', 'App\Http\Controllers\WhatsappAccountController@update')->name('update');
            Route::delete('/{account}', 'App\Http\Controllers\WhatsappAccountController@destroy')->name('destroy');
            Route::post('/{account}/default', 'App\Http\Controllers\WhatsappAccountController@setDefault')->name('setDefault');
            Route::post('/{account}/test', 'App\Http\Controllers\WhatsappAccountController@testSend')->name('test');
        });

        // ── Plantillas ──────────────────────────────────────────────────────────
        Route::prefix('{tenant}/templates')->name('templates.')->where(['tenant' => '[0-9]+'])->group(function () {
            Route::get('/', 'App\Http\Controllers\WhatsappTemplateController@index')->name('index');
            Route::get('/create', 'App\Http\Controllers\WhatsappTemplateController@create')->name('create');
            Route::post('/', 'App\Http\Controllers\WhatsappTemplateController@store')->name('store');
            Route::get('/{template}/edit', 'App\Http\Controllers\WhatsappTemplateController@edit')->name('edit');
            Route::put('/{template}', 'App\Http\Controllers\WhatsappTemplateController@update')->name('update');
            Route::delete('/{template}', 'App\Http\Controllers\WhatsappTemplateController@destroy')->name('destroy');
        });

        // ── Automatizaciones ────────────────────────────────────────────────────
        Route::prefix('{tenant}/automations')->name('automations.')->where(['tenant' => '[0-9]+'])->group(function () {
            Route::get('/', 'App\Http\Controllers\WhatsappAutomationController@index')->name('index');
            Route::get('/create', 'App\Http\Controllers\WhatsappAutomationController@create')->name('create');
            Route::post('/', 'App\Http\Controllers\WhatsappAutomationController@store')->name('store');
            Route::get('/{automation}/edit', 'App\Http\Controllers\WhatsappAutomationController@edit')->name('edit');
            Route::put('/{automation}', 'App\Http\Controllers\WhatsappAutomationController@update')->name('update');
            Route::delete('/{automation}', 'App\Http\Controllers\WhatsappAutomationController@destroy')->name('destroy');
            Route::post('/{automation}/toggle', 'App\Http\Controllers\WhatsappAutomationController@toggle')->name('toggle');
        });
    });
    */
});

// ─────────────────────────────────────────────────────────────────────────────
// Baileys WhatsApp - Controlador de Cobro (Sin costos Meta)
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['web', 'auth'])->prefix('baileys-cobro')->name('baileys.cobro.')->group(function () {
    Route::get('status', 'App\Http\Controllers\BaileysCobroController@status')
        ->name('status');
    Route::post('recordatorio/{cliente}', 'App\Http\Controllers\BaileysCobroController@enviarRecordatorio')
        ->name('recordatorio');
    Route::post('mensaje/{cliente}', 'App\Http\Controllers\BaileysCobroController@enviarMensajePersonalizado')
        ->name('mensaje');
});

// WhatsApp Webhook (Meta Cloud API - sin CSRF)
Route::prefix('whatsapp/webhook')->name('whatsapp.webhook.')->group(function () {
    Route::get('/', 'App\Http\Controllers\WhatsAppWebhookController@verify')
        ->name('verify');
    Route::post('/', 'App\Http\Controllers\WhatsAppWebhookController@receive')
        ->name('receive');
});

// Mapa de ruta de cobro (vista interna, requiere autenticación)
Route::get('/ruta-mapa/{ruta}', function (RutaCobro $ruta) {
    return view('ruta-mapa-public', ['record' => $ruta]);
})->middleware(['web', 'auth', 'can:View:RutaCobro'])->name('ruta.mapa');

// Descarga de backups (solo super_admin)
Route::get('/administrativo/backups/download/{path}', function (string $path) {
    abort_unless(auth()->user()?->hasRole('super_admin'), 403);
    $filePath = base64_decode($path);
    abort_unless(Storage::disk('local')->exists($filePath), 404);
    return Storage::disk('local')->download($filePath);
})->middleware(['web', 'auth'])->name('filament.administrativo.pages.backups.download');

// ── WhatsApp Center (Baileys Integration) ──
Route::middleware(['web', 'auth'])->prefix('whatsapp-center')->name('whatsapp-center.')->group(function () {
    Route::get('/', 'App\Http\Controllers\WhatsAppCenterController@index')->name('dashboard');
    
    // API Endpoints (sin CSRF para facilitar llamadas desde JavaScript)
    Route::withoutMiddleware('web')->middleware('auth:web')->group(function () {
        Route::post('/api/send', 'App\Http\Controllers\WhatsAppCenterController@sendMessage')->name('send')->withoutMiddleware('csrf');
        Route::get('/api/status', 'App\Http\Controllers\WhatsAppCenterController@status')->name('status');
        Route::get('/api/info', 'App\Http\Controllers\WhatsAppCenterController@info')->name('info');
        Route::post('/api/disconnect', 'App\Http\Controllers\WhatsAppCenterController@disconnect')->name('disconnect')->withoutMiddleware('csrf');
        Route::post('/api/reconnect', 'App\Http\Controllers\WhatsAppCenterController@reconnect')->name('reconnect')->withoutMiddleware('csrf');
        Route::get('/api/qrcode', 'App\Http\Controllers\WhatsAppCenterController@qrcode')->name('qrcode');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Publicar actualizaciones de la app móvil (APK)
Route::middleware(['web', 'auth', 'can:Publicar:AppUpdate'])->prefix('admin/update')->name('admin.update.')->group(function () {
    Route::get('/', 'App\Http\Controllers\AppUpdateController@index')->name('index');
    Route::post('/upload', 'App\Http\Controllers\AppUpdateController@upload')->name('upload');
});
