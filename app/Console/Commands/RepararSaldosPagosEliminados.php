<?php

namespace App\Console\Commands;

use App\Models\Venta;
use App\Services\EliminarPagoVentaService;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class RepararSaldosPagosEliminados extends Command
{
    protected $signature = 'pagos:reparar-saldos
                            {--venta= : ID de una venta específica a reparar (si ya la conoces)}
                            {--desde= : Solo revisar el historial de pagos eliminados desde esta fecha (Y-m-d)}
                            {--apply : Aplica los cambios. Sin este flag solo se muestra un reporte, sin tocar nada (dry-run)}';

    protected $description = 'Recalcula monto_pagado/saldo_pendiente/cuotas/saldo del cliente para ventas '
        .'afectadas por el bug de "Eliminar pago" en Resumen del Día, que dejaba el saldo en $0 en vez '
        .'del valor correcto.';

    public function handle(): int
    {
        $ventaIds = $this->option('venta')
            ? collect([(int) $this->option('venta')])
            : $this->ventaIdsDesdeHistorial();

        if ($ventaIds->isEmpty()) {
            $this->info('No se encontraron ventas para revisar.');

            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');
        $this->info(($apply ? 'APLICANDO CAMBIOS' : 'MODO DE PRUEBA (dry-run, no se modifica nada)').' — '.$ventaIds->count().' venta(s) a revisar.');
        $this->newLine();

        foreach ($ventaIds as $ventaId) {
            $venta = Venta::find($ventaId);

            if (! $venta) {
                $this->warn("Venta #{$ventaId} ya no existe, se omite.");

                continue;
            }

            $totalReal = round((float) $venta->prima + (float) $venta->pagos()->sum('monto'), 2);
            $saldoReal = max(0, round((float) $venta->total - $totalReal, 2));

            $this->line(sprintf(
                '%s (#%d) — cliente #%s — %s',
                $venta->numero_venta,
                $venta->id,
                $venta->cliente_id,
                $venta->cliente?->nombre_completo ?? 'sin cliente'
            ));
            $this->line(sprintf(
                '  monto_pagado:    %s  ->  %s',
                number_format((float) $venta->monto_pagado, 2),
                number_format($totalReal, 2)
            ));
            $this->line(sprintf(
                '  saldo_pendiente: %s  ->  %s',
                number_format((float) $venta->saldo_pendiente, 2),
                number_format($saldoReal, 2)
            ));

            if (round((float) $venta->monto_pagado, 2) === $totalReal && round((float) $venta->saldo_pendiente, 2) === $saldoReal) {
                $this->comment('  ✓ ya está correcta, no requiere cambios.');
                $this->newLine();

                continue;
            }

            if ($apply) {
                EliminarPagoVentaService::recalcularVentaCompleta($venta->id);
                $this->info('  ✔ corregida y cuotas re-sincronizadas.');
            }

            $this->newLine();
        }

        if (! $apply) {
            $this->comment('Nada se modificó. Vuelve a correr el comando agregando --apply para aplicar los cambios.');
        }

        return self::SUCCESS;
    }

    private function ventaIdsDesdeHistorial(): \Illuminate\Support\Collection
    {
        $query = Activity::where('log_name', 'pagos_eliminados');

        if ($desde = $this->option('desde')) {
            $query->whereDate('created_at', '>=', $desde);
        }

        return $query->get()
            ->flatMap(fn (Activity $a) => collect($a->properties->get('pagos_eliminados', []))->pluck('venta_id'))
            ->filter()
            ->unique()
            ->values();
    }
}
