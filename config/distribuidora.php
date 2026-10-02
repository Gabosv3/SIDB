<?php

return [
    // Punto de partida de todas las rutas de cobro (la distribuidora). Lo usa
    // ClientesRutaController::sugerirOrden() para que el orden sugerido
    // arranque siempre desde aquí. Se puede sobreescribir desde el .env.
    'origen' => [
        'lat' => (float) env('DISTRIBUIDORA_LAT', 13.348134510384128),
        'lng' => (float) env('DISTRIBUIDORA_LNG', -88.40815076088957),
    ],
];
