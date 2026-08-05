<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cada mañana antes de que los cobradores salgan a ruta.
Schedule::command('app:notificar-cuotas-vencidas')->dailyAt('06:00');
