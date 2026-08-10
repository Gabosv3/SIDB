<?php

namespace App\Console\Commands;

use App\Models\PagoVenta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerarRecibosHistoricos extends Command
{
    protected $signature = 'pagos:generar-recibos-historicos
                            {--apply : Aplica los cambios. Sin este flag solo se muestra un reporte, sin tocar nada (dry-run)}';

    protected $description = 'Asigna un numero_recibo retroactivo (formato REC-HIST-NNNNNN) a los pagos '
        .'importados en bloque antes de que existiera el correlativo por cobrador (Saldo inicial importado, '
        .'Ingreso masivo, etc.), para que también se les pueda generar ticket. Pagos del mismo venta_id+fecha_pago '
        .'(un solo evento repartido en varias cuotas) comparten un mismo número. Se numeran por fecha_pago '
        .'ascendente. Corre en modo de prueba por defecto; agrega --apply para modificar datos.';

    public function handle(): int
    {
        $aplicar = (bool) $this->option('apply');

        $grupos = PagoVenta::whereNull('numero_recibo')
            ->whereNull('anulado_en')
            ->with('cliente:id,nombre,apellido')
            ->orderBy('fecha_pago')
            ->orderBy('venta_id')
            ->get()
            ->groupBy(fn (PagoVenta $p) => $p->venta_id.'|'.$p->fecha_pago->toDateString());

        if ($grupos->isEmpty()) {
            $this->info('No hay pagos sin numero_recibo. Nada que hacer.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d pagos sin recibo, agrupados en %d recibos históricos a generar.',
            $grupos->sum(fn ($g) => $g->count()),
            $grupos->count()
        ));

        $consecutivo = 0;
        $filas = [];

        DB::transaction(function () use ($grupos, $aplicar, &$consecutivo, &$filas) {
            foreach ($grupos as $grupo) {
                $consecutivo++;
                $numeroRecibo = sprintf('REC-HIST-%06d', $consecutivo);
                $primero = $grupo->first();

                $filas[] = [
                    $numeroRecibo,
                    $primero->fecha_pago->format('d/m/Y'),
                    $primero->cliente?->nombre_completo ?? "cliente #{$primero->cliente_id}",
                    $grupo->count(),
                    number_format((float) $grupo->sum('monto'), 2),
                ];

                if ($aplicar) {
                    PagoVenta::whereIn('id', $grupo->pluck('id'))->update(['numero_recibo' => $numeroRecibo]);
                }
            }
        });

        $this->table(['Recibo', 'Fecha', 'Cliente', 'Pagos', 'Monto'], array_slice($filas, 0, 20));

        if (count($filas) > 20) {
            $this->line('... y '.(count($filas) - 20).' recibos más.');
        }

        if (! $aplicar) {
            $this->warn('Modo de prueba (dry-run) — no se guardó nada. Volvé a correr con --apply para aplicar.');

            return self::SUCCESS;
        }

        $this->info("Listo. Se generaron {$consecutivo} recibos históricos.");

        return self::SUCCESS;
    }
}
