<?php

use App\Models\RutaCobro;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/administrativo');
});

// Asignaciones diarias
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/asignaciones-diarias/crear', 'App\Http\Controllers\AsignacionDiariaController@crear')->name('asignacion-diaria.crear');
    Route::post('/asignaciones-diarias/guardar', 'App\Http\Controllers\AsignacionDiariaController@guardar')->name('asignacion-diaria.guardar');
    Route::get('/asignaciones-diarias/{asignacion}/editar', 'App\Http\Controllers\AsignacionDiariaController@editar')->name('asignacion-diaria.editar');
    Route::post('/asignaciones-diarias/{asignacion}/actualizar', 'App\Http\Controllers\AsignacionDiariaController@actualizar')->name('asignacion-diaria.actualizar');
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
