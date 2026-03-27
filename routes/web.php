<?php

use App\Models\RutaCobro;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/administrativo');
});

// Mapa público para rutas de cobro
Route::get('/ruta-mapa/{ruta}', function (RutaCobro $ruta) {
    return view('ruta-mapa-public', ['record' => $ruta]);
})->name('ruta.mapa');
