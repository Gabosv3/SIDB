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

// Productos
Route::middleware(['web', 'auth'])->prefix('productos')->name('productos.')->group(function () {
    Route::get('{tenant}/conteo-inventario', 'App\Http\Controllers\ProductoController@generarConteoInventario')
        ->name('conteo-inventario')
        ->where('tenant', '[0-9]+');
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
    Route::get('{tenant}/productos-buscar', 'App\Http\Controllers\AsignacionDiariaController@buscarProductos')
        ->name('productos-buscar')
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
    Route::post('{tenant}/monitor/dispositivos/{device}/liberar', 'App\Http\Controllers\PosMonitorController@liberarDispositivo')
        ->middleware('can:Gestionar:DispositivosPos')
        ->name('monitor.liberar')
        ->where('tenant', '[0-9]+');
    Route::post('{tenant}/monitor/dispositivos/{device}/actualizar', 'App\Http\Controllers\PosMonitorController@actualizarDatos')
        ->middleware('can:Gestionar:DispositivosPos')
        ->name('monitor.actualizar')
        ->where('tenant', '[0-9]+');
    Route::delete('{tenant}/monitor/dispositivos/{device}', 'App\Http\Controllers\PosMonitorController@eliminarDispositivo')
        ->middleware('can:Gestionar:DispositivosPos')
        ->name('monitor.eliminar')
        ->where('tenant', '[0-9]+');
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
    Route::get('{tenant}/exportar/excel', 'App\Http\Controllers\ClientesRutaController@exportarExcel')
        ->name('exportar-excel')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/exportar/word', 'App\Http\Controllers\ClientesRutaController@exportarWord')
        ->name('exportar-word')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/clientes/{cliente}/detalle', 'App\Http\Controllers\ClientesRutaController@detalleCliente')
        ->name('detalle')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::get('{tenant}/clientes/{cliente}/perfil', 'App\Http\Controllers\ClientesRutaController@perfilCliente')
        ->name('perfil-cliente')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::get('{tenant}/clientes/{cliente}/estado-cuenta', 'App\Http\Controllers\ClientesRutaController@generarEstadoCuenta')
        ->name('estado-cuenta')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::get('{tenant}/recibo/{numeroRecibo}', 'App\Http\Controllers\ClientesRutaController@generarRecibo')
        ->name('recibo')
        ->where(['tenant' => '[0-9]+', 'numeroRecibo' => 'REC-(HIST|[0-9]+)-[0-9]+']);
    Route::post('{tenant}/recibo/{numeroRecibo}/anular', 'App\Http\Controllers\ClientesRutaController@anularRecibo')
        ->name('recibo.anular')
        ->where(['tenant' => '[0-9]+', 'numeroRecibo' => 'REC-(HIST|[0-9]+)-[0-9]+']);
    Route::get('{tenant}/historial', 'App\Http\Controllers\ClientesRutaController@historialGeneral')
        ->name('historial')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/historial/data', 'App\Http\Controllers\ClientesRutaController@historialData')
        ->name('historial-data')
        ->where('tenant', '[0-9]+');
    Route::post('{tenant}/reordenar', 'App\Http\Controllers\ClientesRutaController@reordenar')
        ->name('reordenar')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/rutas/{rutaId}/sugerir-orden', 'App\Http\Controllers\ClientesRutaController@sugerirOrden')
        ->name('sugerir-orden')
        ->where(['tenant' => '[0-9]+', 'rutaId' => '[0-9]+']);
    Route::post('{tenant}/fusionar-rutas', 'App\Http\Controllers\ClientesRutaController@fusionarRutas')
        ->name('fusionar-rutas')
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
    Route::post('{tenant}/clientes/{cliente}/venta-vendedor-fecha', 'App\Http\Controllers\ClientesRutaController@actualizarVentaVendedorFecha')
        ->name('venta-vendedor-fecha')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/clientes/{cliente}/campo', 'App\Http\Controllers\ClientesRutaController@actualizarCampo')
        ->name('campo')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/clientes/{cliente}/ubicacion', 'App\Http\Controllers\ClientesRutaController@actualizarUbicacion')
        ->name('ubicacion')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/clientes/{cliente}/revisado', 'App\Http\Controllers\ClientesRutaController@marcarRevisado')
        ->name('revisado')
        ->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/limpiar-revision', 'App\Http\Controllers\ClientesRutaController@limpiarRevision')
        ->name('limpiar-revision')
        ->where('tenant', '[0-9]+');
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
// WhatsApp Monitor (WhatsApp Coexistence, solo lectura)
Route::middleware(['web', 'auth', 'can:View:WhatsAppCenter'])->prefix('whatsapp-center')->name('whatsapp-center.')->group(function () {
    Route::get('{tenant}', 'App\Http\Controllers\WhatsAppCenterController@dashboard')
        ->name('index')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/dashboard-data', 'App\Http\Controllers\WhatsAppCenterController@dashboardData')
        ->name('dashboard-data')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/conversaciones', 'App\Http\Controllers\WhatsAppCenterController@conversaciones')
        ->name('conversaciones')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/data', 'App\Http\Controllers\WhatsAppCenterController@data')
        ->name('data')
        ->where('tenant', '[0-9]+');
    Route::get('{tenant}/conversaciones/{conversacion}/mensajes', 'App\Http\Controllers\WhatsAppCenterController@mensajes')
        ->name('mensajes')
        ->where(['tenant' => '[0-9]+', 'conversacion' => '[0-9]+']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Perfil de Empleado
Route::middleware(['web', 'auth', 'can:View:PerfilEmpleado'])->prefix('empleados')->name('empleados.')->group(function () {
    Route::get('{tenant}/ficha-datos-blanco', 'App\Http\Controllers\EmpleadoPerfilController@generarFichaDatos')
        ->name('ficha-datos-blanco')
        ->where('tenant', '[0-9]+');
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
    Route::post('{tenant}/{user}/pagos', 'App\Http\Controllers\EmpleadoPerfilController@registrarPago')
        ->name('registrarPago')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
    Route::delete('{tenant}/{user}/pagos/{pago}', 'App\Http\Controllers\EmpleadoPerfilController@eliminarPago')
        ->name('eliminarPago')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+', 'pago' => '[0-9]+']);
    Route::get('{tenant}/{user}/pagos/{pago}/constancia', 'App\Http\Controllers\EmpleadoPerfilController@generarConstancia')
        ->name('constanciaPago')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+', 'pago' => '[0-9]+']);
    Route::get('{tenant}/{user}/contrato', 'App\Http\Controllers\EmpleadoPerfilController@generarContrato')
        ->name('contrato')
        ->where(['tenant' => '[0-9]+', 'user' => '[0-9]+']);
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

// ─────────────────────────────────────────────────────────────────────────────
// Publicar actualizaciones de la app móvil (APK)
Route::middleware(['web', 'auth', 'can:Publicar:AppUpdate'])->prefix('admin/update')->name('admin.update.')->group(function () {
    Route::get('/', 'App\Http\Controllers\AppUpdateController@index')->name('index');
    Route::post('/upload', 'App\Http\Controllers\AppUpdateController@upload')->name('upload');
});
