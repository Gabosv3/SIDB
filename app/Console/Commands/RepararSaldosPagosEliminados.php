<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Venta;
use App\Services\EliminarPagoVentaService;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class RepararSaldosPagosEliminados extends Command
{
    /**
     * Diferencias menores a esto se consideran ruido de punto flotante, no
     * una inconsistencia real (los decimal:2 de Eloquent ya deberían evitarlo,
     * pero se compara con tolerancia para no reportar falsos positivos).
     */
    private const EPSILON = 0.005;

    /**
     * Si el cambio en monto_pagado supera este monto absoluto, la venta se
     * trata como "riesgosa" y se excluye de --apply salvo que se pida
     * explícitamente con --incluir-riesgosas.
     */
    private const UMBRAL_RIESGO = 20.0;

    protected $signature = 'pagos:reparar-saldos
                            {--venta= : ID interno de una venta específica a reparar}
                            {--numero-venta= : Número de venta tal como se ve en pantalla (ej: VNT-766FD781)}
                            {--cliente= : Revisa todas las ventas de un cliente (ID o código anterior)}
                            {--activity : En vez de escanear todo, solo revisa ventas que aparecen en el historial de "Pagos Eliminados"}
                            {--desde= : Junto con --activity, filtra el historial desde esta fecha (Y-m-d)}
                            {--incluir-riesgosas : Incluye en --apply las ventas riesgosas (reaperturas de ventas ya completadas, o cambios grandes). Por defecto se excluyen y solo se listan para revisión manual.}
                            {--apply : Aplica los cambios. Sin este flag solo se muestra un reporte, sin tocar nada (dry-run)}';

    protected $description = 'Detecta y corrige ventas cuyo monto_pagado/saldo_pendiente guardado no coincide '
        .'con la suma real de sus pagos (prima + pago_ventas). Sin opciones, escanea TODAS las ventas del '
        .'sistema. Corre en modo de prueba por defecto; agrega --apply para modificar datos. Las ventas '
        .'riesgosas (p.ej. reabrir una venta que hoy se ve pagada al 100%) se excluyen de --apply por defecto.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $incluirRiesgosas = (bool) $this->option('incluir-riesgosas');

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

        $seguras = [];
        $riesgosas = [];
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

            if (abs($montoActual - $totalReal) < self::EPSILON && abs($saldoActual - $saldoReal) < self::EPSILON) {
                continue;
            }

            $motivoRiesgo = match (true) {
                $saldoActual < self::EPSILON && $saldoReal > self::EPSILON => 'reabre una venta que hoy se ve pagada al 100%',
                abs($montoActual - $totalReal) > self::UMBRAL_RIESGO => sprintf('cambio grande ($%s)', number_format(abs($montoActual - $totalReal), 2)),
                default => null,
            };

            $item = compact('venta', 'totalReal', 'saldoReal', 'montoActual', 'saldoActual', 'motivoRiesgo');
            $motivoRiesgo ? $riesgosas[] = $item : $seguras[] = $item;
        }

        if (! empty($seguras)) {
            $this->line('── Ajustes menores '.($apply ? '(aplicando)' : '(dry-run)').' ──');
            foreach ($seguras as $item) {
                $this->reportarVenta($item);
                if ($apply) {
                    EliminarPagoVentaService::recalcularVentaCompleta($item['venta']->id);
                    $this->info('  ✔ corregida y cuotas re-sincronizadas.');
                }
                $this->newLine();
            }
        }

        if (! empty($riesgosas)) {
            $this->warn('── Ventas riesgosas — requieren revisión manual antes de aplicar ──');
            foreach ($riesgosas as $item) {
                $this->reportarVenta($item);
                $this->warn('  ⚠ '.$item['motivoRiesgo']);
                if ($apply && $incluirRiesgosas) {
                    EliminarPagoVentaService::recalcularVentaCompleta($item['venta']->id);
                    $this->info('  ✔ corregida (forzado con --incluir-riesgosas).');
                } elseif ($apply) {
                    $this->comment('  → omitida. Verifica los pagos reales de esta venta antes de correr con --incluir-riesgosas.');
                }
                $this->newLine();
            }
        }

        $this->comment(sprintf(
            'Revisadas: %d. Ajustes menores: %d. Riesgosas: %d.',
            $revisadas,
            count($seguras),
            count($riesgosas)
        ));

        if (! $apply && (count($seguras) || count($riesgosas))) {
            $this->comment('Nada se modificó. Vuelve a correr el comando agregando --apply para aplicar los ajustes menores.');
        }

        return self::SUCCESS;
    }

    private function reportarVenta(array $item): void
    {
        $venta = $item['venta'];

        $this->line(sprintf(
            '%s (#%d) — cliente #%s — %s',
            $venta->numero_venta,
            $venta->id,
            $venta->cliente_id,
            $venta->cliente?->nombre_completo ?? 'sin cliente'
        ));
        $this->line(sprintf('  monto_pagado:    %s  ->  %s', number_format($item['montoActual'], 2), number_format($item['totalReal'], 2)));
        $this->line(sprintf('  saldo_pendiente: %s  ->  %s', number_format($item['saldoActual'], 2), number_format($item['saldoReal'], 2)));
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

                    if (abs(round((float) $venta->monto_pagado, 2) - $totalReal) >= self::EPSILON
                        || abs(round((float) $venta->saldo_pendiente, 2) - $saldoReal) >= self::EPSILON) {
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
