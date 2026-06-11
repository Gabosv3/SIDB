<?php

use App\Models\RutaCobro;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/administrativo');
});

// Exportación de clientes
Route::middleware(['web', 'auth'])->prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/exportar/csv', 'App\Http\Controllers\ClienteExportController@exportarSimpleCSV')->name('exportar.csv');
});

// Pagos y Ubicación (masivo)
Route::middleware(['web', 'auth'])->prefix('pagos-ubicacion')->name('pagos-ubicacion.')->group(function () {
    Route::get('/', 'App\Http\Controllers\PagosYUbicacionController@index')->name('index');
    Route::post('/procesar', 'App\Http\Controllers\PagosYUbicacionController@procesar')->name('procesar');
});

// Asignaciones diarias (rutas protegidas por autenticación)
Route::middleware(['web', 'auth'])->prefix('asignaciones-diarias')->name('asignacion-diaria.')->group(function () {
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
Route::middleware(['web', 'auth'])->prefix('reportes')->name('reporte.')->group(function () {
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
// WhatsApp Center
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['web', 'auth'])->prefix('whatsapp')->name('whatsapp.')->group(function () {

    // ── Centro principal ──────────────────────────────────────────────────────
    Route::get('{tenant}', 'App\Http\Controllers\WhatsappController@index')
        ->name('index')->where('tenant', '[0-9]+');
    Route::get('{tenant}/conversation/{conversation}', 'App\Http\Controllers\WhatsappController@show')
        ->name('show')->where(['tenant' => '[0-9]+', 'conversation' => '[0-9]+']);
    Route::post('{tenant}/send', 'App\Http\Controllers\WhatsappController@send')
        ->name('send')->where('tenant', '[0-9]+');
    Route::post('{tenant}/reminder/{cliente}', 'App\Http\Controllers\WhatsappController@sendReminder')
        ->name('sendReminder')->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::get('{tenant}/open/{cliente}', 'App\Http\Controllers\WhatsappController@openClient')
        ->name('openClient')->where(['tenant' => '[0-9]+', 'cliente' => '[0-9]+']);
    Route::post('{tenant}/quick-message', 'App\Http\Controllers\WhatsappController@quickMessage')
        ->name('quickMessage')->where('tenant', '[0-9]+');

    // ── Cuentas (números conectados) ──────────────────────────────────────────
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

    // ── Plantillas ────────────────────────────────────────────────────────────
    Route::prefix('{tenant}/templates')->name('templates.')->where(['tenant' => '[0-9]+'])->group(function () {
        Route::get('/', 'App\Http\Controllers\WhatsappTemplateController@index')->name('index');
        Route::get('/create', 'App\Http\Controllers\WhatsappTemplateController@create')->name('create');
        Route::post('/', 'App\Http\Controllers\WhatsappTemplateController@store')->name('store');
        Route::get('/{template}/edit', 'App\Http\Controllers\WhatsappTemplateController@edit')->name('edit');
        Route::put('/{template}', 'App\Http\Controllers\WhatsappTemplateController@update')->name('update');
        Route::delete('/{template}', 'App\Http\Controllers\WhatsappTemplateController@destroy')->name('destroy');
    });

    // ── Automatizaciones ──────────────────────────────────────────────────────
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

// WhatsApp Webhook (Meta Cloud API - sin CSRF)
Route::prefix('whatsapp/webhook')->name('whatsapp.webhook.')->group(function () {
    Route::get('/', 'App\Http\Controllers\WhatsAppWebhookController@verify')
        ->name('verify');
    Route::post('/', 'App\Http\Controllers\WhatsAppWebhookController@receive')
        ->name('receive');
});

// Mapa público para rutas de cobro
Route::get('/ruta-mapa/{ruta}', function (RutaCobro $ruta) {
    return view('ruta-mapa-public', ['record' => $ruta]);
})->name('ruta.mapa');

// Descarga de backups (solo super_admin)
Route::get('/administrativo/backups/download/{path}', function (string $path) {
    abort_unless(auth()->user()?->hasRole('super_admin'), 403);
    $filePath = base64_decode($path);
    abort_unless(Storage::disk('local')->exists($filePath), 404);
    return Storage::disk('local')->download($filePath);
})->middleware(['web', 'auth'])->name('filament.administrativo.pages.backups.download');
