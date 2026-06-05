<?php

use App\Models\RutaCobro;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/administrativo');
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
