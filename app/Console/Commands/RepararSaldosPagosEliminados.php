<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Venta;
use App\Services\EliminarPagoVentaService;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class RepararSaldosPagosEliminados extends Command
{
    protected $signature = 'pagos:reparar-saldos
                            {--venta= : ID interno de una venta específica a reparar}
                            {--numero-venta= : Número de venta tal como se ve en pantalla (ej: VNT-766FD781)}
                            {--cliente= : Revisa todas las ventas de un cliente (ID o código anterior)}
                            {--activity : En vez de escanear todo, solo revisa ventas que aparecen en el historial de "Pagos Eliminados"}
                            {--desde= : Junto con --activity, filtra el historial desde esta fecha (Y-m-d)}
                            {--apply : Aplica los cambios. Sin este flag solo se muestra un reporte, sin tocar nada (dry-run)}';

    protected $description = 'Detecta y corrige ventas cuyo monto_pagado/saldo_pendiente guardado no coincide '
        .'con la suma real de sus pagos (prima + pago_ventas). Sin opciones, escanea TODAS las ventas del '
        .'sistema. Corre en modo de prueba por defecto; agrega --apply para modificar datos.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $ventaIds = $this->resolverVentaIds();

        if ($ventaIds === null) {
            return self::FAILURE;
        }

        if ($ventaIds->isEmpty()) {
            $this->info('No se encontraron ventas para revisar.');

            return self::SUCCESS;
        }

        $this->info(($apply ? 'APLICANDO CAMBIOS' : 'MODO DE PRUEBA (dry-run, no se modifica nada)').' — revisando '.$ventaIds->count().' venta(s).');
        $this->newLine();

        $corregidas = 0;
        $revisadas = 0;

        foreach ($ventaIds as $ventaId) {
            $venta = Venta::find($ventaId);

            if (! $venta) {
                $this->warn("Venta #{$ventaId} ya no existe, se omite.");

                continue;
            }

            $revisadas++;

            $totalReal = round((float) $venta->prima + (float) $venta->pagos()->sum('monto'), 2);
            $saldoReal = max(0, round((float) $venta->total - $totalReal, 2));
            $montoActual = round((float) $venta->monto_pagado, 2);
            $saldoActual = round((float) $venta->saldo_pendiente, 2);

            if ($montoActual === $totalReal && $saldoActual === $saldoReal) {
                continue;
            }

            $corregidas++;

            $this->line(sprintf(
                '%s (#%d) — cliente #%s — %s',
                $venta->numero_venta,
                $venta->id,
                $venta->cliente_id,
                $venta->cliente?->nombre_completo ?? 'sin cliente'
            ));
            $this->line(sprintf('  monto_pagado:    %s  ->  %s', number_format($montoActual, 2), number_format($totalReal, 2)));
            $this->line(sprintf('  saldo_pendiente: %s  ->  %s', number_format($saldoActual, 2), number_format($saldoReal, 2)));

            if ($apply) {
                EliminarPagoVentaService::recalcularVentaCompleta($venta->id);
                $this->info('  ✔ corregida y cuotas re-sincronizadas.');
            }

            $this->newLine();
        }

        $this->comment("Revisadas: {$revisadas}. Con inconsistencia: {$corregidas}.");

        if (! $apply && $corregidas > 0) {
            $this->comment('Nada se modificó. Vuelve a correr el comando agregando --apply para aplicar los cambios.');
        }

        return self::SUCCESS;
    }

    private function resolverVentaIds(): ?\Illuminate\Support\Collection
    {
        if ($this->option('venta')) {
            return collect([(int) $this->option('venta')]);
        }

        if ($numero = $this->option('numero-venta')) {
            $venta = Venta::where('numero_venta', $numero)->first();

            if (! $venta) {
                $this->error("No existe ninguna venta con numero_venta = {$numero}.");

                return null;
            }

            return collect([$venta->id]);
        }

        if ($clienteParam = $this->option('cliente')) {
            $cliente = is_numeric($clienteParam)
                ? Cliente::find((int) $clienteParam)
                : Cliente::where('codigo_anterior', $clienteParam)->first();

            if (! $cliente) {
                $this->error("No se encontró ningún cliente con ID o código anterior = {$clienteParam}.");

                return null;
            }

            return $cliente->ventas()->pluck('id');
        }

        if ($this->option('activity')) {
            return $this->ventaIdsDesdeHistorial();
        }

        return $this->ventaIdsEscaneandoTodo();
    }

    /**
     * Escanea toda la tabla de ventas comparando monto_pagado/saldo_pendiente
     * guardados contra el valor real (prima + suma de pagos vigentes),
     * usando withSum para evitar una consulta por venta.
     */
    private function ventaIdsEscaneandoTodo(): \Illuminate\Support\Collection
    {
        $afectadas = collect();

        Venta::query()
            ->withSum('pagos', 'monto')
            ->orderBy('id')
            ->chunkById(200, function ($ventas) use (&$afectadas) {
                foreach ($ventas as $venta) {
                    $totalReal = round((float) $venta->prima + (float) ($venta->pagos_sum_monto ?? 0), 2);
                    $saldoReal = max(0, round((float) $venta->total - $totalReal, 2));

                    if (round((float) $venta->monto_pagado, 2) !== $totalReal
                        || round((float) $venta->saldo_pendiente, 2) !== $saldoReal) {
                        $afectadas->push($venta->id);
                    }
                }
            });

        return $afectadas;
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
