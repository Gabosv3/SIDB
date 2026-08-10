<?php

namespace App\Console\Commands;

use App\Models\Venta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerarNumerosVentaHistoricos extends Command
{
    protected $signature = 'ventas:generar-numeros-historicos
                            {--apply : Aplica los cambios. Sin este flag solo se muestra un reporte, sin tocar nada (dry-run)}';

    protected $description = 'Reemplaza el numero_venta feo con codigo aleatorio (VNT-XXXXXXXX, formato viejo '
        .'de antes del correlativo TCK-{vendedor}-{numero}) por un formato ordenado VNT-HIST-NNNNNN, numerado '
        .'por fecha_venta ascendente. No toca ventas que ya tienen el formato TCK- real. Corre en modo de '
        .'prueba por defecto; agrega --apply para modificar datos.';

    public function handle(): int
    {
        $aplicar = (bool) $this->option('apply');

        $ventas = Venta::where('numero_venta', 'REGEXP', '^VNT-[A-Z0-9]{8}$')
            ->with('cliente:id,nombre,apellido')
            ->orderBy('fecha_venta')
            ->orderBy('id')
            ->get();

        if ($ventas->isEmpty()) {
            $this->info('No hay ventas con el código feo. Nada que hacer.');

            return self::SUCCESS;
        }

        $this->info("{$ventas->count()} ventas con código feo (VNT-XXXXXXXX) a renumerar.");

        $consecutivo = 0;
        $filas = [];

        DB::transaction(function () use ($ventas, $aplicar, &$consecutivo, &$filas) {
            foreach ($ventas as $venta) {
                $consecutivo++;
                $numeroNuevo = sprintf('VNT-HIST-%06d', $consecutivo);

                $filas[] = [
                    $venta->numero_venta,
                    $numeroNuevo,
                    $venta->fecha_venta?->format('d/m/Y'),
                    $venta->cliente?->nombre_completo ?? "cliente #{$venta->cliente_id}",
                ];

                if ($aplicar) {
                    $venta->update(['numero_venta' => $numeroNuevo]);
                }
            }
        });

        $this->table(['Antes', 'Después', 'Fecha', 'Cliente'], array_slice($filas, 0, 20));

        if (count($filas) > 20) {
            $this->line('... y '.(count($filas) - 20).' ventas más.');
        }

        if (! $aplicar) {
            $this->warn('Modo de prueba (dry-run) — no se guardó nada. Volvé a correr con --apply para aplicar.');

            return self::SUCCESS;
        }

        $this->info("Listo. Se renumeraron {$consecutivo} ventas.");

        return self::SUCCESS;
    }
}
