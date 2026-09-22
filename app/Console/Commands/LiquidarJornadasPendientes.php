<?php

namespace App\Console\Commands;

use App\Models\AsignacionDiaria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

// Corre una vez al día (ver routes/console.php), a las 11pm -- si un
// vendedor terminó su jornada y nadie le dio "Liquidar jornada" a mano,
// esto la liquida solo para que las devoluciones de stock y el corte de
// inventario queden reflejados aunque nadie haya entrado a cerrarla.
class LiquidarJornadasPendientes extends Command
{
    protected $signature = 'app:liquidar-jornadas-pendientes';

    protected $description = 'Liquida automáticamente las asignaciones diarias de hoy que quedaron activas (sin liquidar a mano)';

    public function handle(): int
    {
        $asignaciones = AsignacionDiaria::where('estado', 'activa')
            ->whereDate('fecha', today())
            ->with('detalles')
            ->get();

        $liquidadas = 0;

        foreach ($asignaciones as $asignacion) {
            try {
                $asignacion->liquidar('Liquidada automáticamente a las 11pm (nadie la cerró a mano)');
                $liquidadas++;
            } catch (\Throwable $e) {
                Log::error('No se pudo liquidar automáticamente la asignación diaria', [
                    'asignacion_id' => $asignacion->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Jornadas liquidadas automáticamente: {$liquidadas}.");

        return self::SUCCESS;
    }
}
