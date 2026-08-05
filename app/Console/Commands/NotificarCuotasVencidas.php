<?php

namespace App\Console\Commands;

use App\Models\Cobrador;
use App\Models\GestionCobro;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

// Corre una vez al día (ver routes/console.php) y le avisa a cada cobrador
// cuántas cuotas de sus rutas vencen hoy/mañana, y cuántas ya están vencidas.
// Antes de esto la app era puramente reactiva: el cobrador solo se enteraba
// de una cuota vencida si la veía en la ruta del día.
class NotificarCuotasVencidas extends Command
{
    protected $signature = 'app:notificar-cuotas-vencidas';

    protected $description = 'Notifica a cada cobrador cuántas cuotas de sus rutas vencen hoy/mañana o ya están vencidas';

    public function handle(PushNotificationService $push): int
    {
        $hoy = now()->toDateString();
        $manana = now()->addDay()->toDateString();

        $cobradores = Cobrador::with('user')->whereHas('user')->get();
        $notificados = 0;

        foreach ($cobradores as $cobrador) {
            $rutasIds = $cobrador->rutasCobro()->pluck('id');
            if ($rutasIds->isEmpty()) {
                continue;
            }

            $baseQuery = GestionCobro::where('estado', 'pendiente')
                ->whereHas('cliente', fn ($q) => $q->whereIn('ruta_cobro_id', $rutasIds));

            $porVencer = (clone $baseQuery)->whereBetween('fecha_vencimiento', [$hoy, $manana])->count();
            $vencidas  = (clone $baseQuery)->where('fecha_vencimiento', '<', $hoy)->count();

            if ($porVencer === 0 && $vencidas === 0) {
                continue;
            }

            $partes = [];
            if ($porVencer > 0) {
                $partes[] = "{$porVencer} cuota" . ($porVencer !== 1 ? 's' : '') . ' vence' . ($porVencer !== 1 ? 'n' : '') . ' hoy o mañana';
            }
            if ($vencidas > 0) {
                $partes[] = "{$vencidas} ya está" . ($vencidas !== 1 ? 'n' : '') . ' vencida' . ($vencidas !== 1 ? 's' : '');
            }

            $push->enviarAUsuario(
                $cobrador->user,
                'Cuotas pendientes',
                implode(', ', $partes) . '.',
                ['tipo' => 'cuotas_pendientes']
            );
            $notificados++;
        }

        $this->info("Notificaciones enviadas a {$notificados} cobrador(es).");

        return self::SUCCESS;
    }
}
