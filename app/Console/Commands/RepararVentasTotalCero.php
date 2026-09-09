<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\GestionCobro;
use App\Models\Venta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Repara ventas a crédito registradas en $0 por un bug ya corregido en
// VentaController: el precio unitario de las líneas a crédito se tomaba del
// precio de contado de la asignación diaria en vez de cuotas × precio_cuota,
// y como muchos productos no tienen precio de contado configurado (solo se
// venden a plazos), el total quedaba en $0 aunque el POS mostrara el monto
// correcto al vendedor. El precio_cuota que mandó la app SÍ quedó guardado
// bien en detalle_ventas, así que de ahí se recalcula todo lo demás.
class RepararVentasTotalCero extends Command
{
    protected $signature = 'ventas:reparar-total-cero {--dry-run : Solo mostrar qué se repararía, sin guardar cambios}';

    protected $description = 'Recalcula ventas a crédito que quedaron registradas en $0 por el bug de precio_venta de la asignación diaria';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $ventas = Venta::where('total', 0)
            ->whereHas('detalles', fn ($q) => $q->where('tipo_pago', 'credito')->where('cuotas', '>', 0)->where('precio_cuota', '>', 0))
            ->with('detalles')
            ->get();

        if ($ventas->isEmpty()) {
            $this->info('No se encontraron ventas en $0 con datos de crédito para reparar.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Ventas a reparar: {$ventas->count()}");

        foreach ($ventas as $venta) {
            $this->reparar($venta, $dryRun);
        }

        $this->info($dryRun ? 'Dry-run terminado — no se guardó nada.' : 'Reparación completada.');
        return self::SUCCESS;
    }

    private function reparar(Venta $venta, bool $dryRun): void
    {
        DB::transaction(function () use ($venta, $dryRun) {
            $subtotal = 0;

            foreach ($venta->detalles as $detalle) {
                if ($detalle->tipo_pago === 'credito' && $detalle->cuotas && $detalle->precio_cuota) {
                    $precioUnitario = round($detalle->cuotas * $detalle->precio_cuota, 2);
                } else {
                    $precioUnitario = (float) $detalle->precio_unitario;
                }

                $dto   = (float) ($detalle->descuento_porcentaje ?? 0);
                $linea = round($detalle->cantidad * $precioUnitario * (1 - $dto / 100), 2);
                $subtotal += $linea;

                if (! $dryRun) {
                    $detalle->update(['precio_unitario' => $precioUnitario, 'subtotal' => $linea]);
                }
            }

            $descuentoPct   = (float) $venta->descuento_porcentaje;
            $descuentoMonto = round($subtotal * $descuentoPct / 100, 2);
            $total          = round($subtotal - $descuentoMonto, 2);

            $totalContado = $venta->detalles->where('tipo_pago', 'contado')->sum(fn ($d) => round($d->cantidad * $d->precio_unitario, 2));
            $totalCredito = $subtotal - $totalContado; // ya con el precio corregido en memoria

            $prima          = (float) $venta->prima;
            $montoPagado    = round($totalContado + $prima, 2);
            $saldoPendiente = max(0, round($totalCredito - $prima, 2));
            $estaCompletada = $saldoPendiente <= 0;

            $this->line(sprintf(
                '  Venta #%d (%s): total $%.2f → $%.2f | saldo_pendiente → $%.2f',
                $venta->id,
                $venta->numero_venta,
                $venta->total,
                $total,
                $saldoPendiente
            ));

            if ($dryRun) {
                return;
            }

            $venta->update([
                'subtotal'        => $subtotal,
                'descuento_monto' => $descuentoMonto,
                'total'           => $total,
                'monto_pagado'    => $montoPagado,
                'saldo_pendiente' => $saldoPendiente,
                'estado'          => $estaCompletada ? 'completada' : 'pendiente',
            ]);

            // Recalcular las cuotas de cobro de esta venta con el saldo correcto.
            $gestiones = GestionCobro::where('venta_id', $venta->id)->orderBy('numero_cuota')->get();
            $numeroCuotas = $gestiones->count();

            if ($numeroCuotas > 0 && $saldoPendiente > 0) {
                $montoBase = floor($saldoPendiente / $numeroCuotas * 100) / 100;
                $residuo   = round($saldoPendiente - ($montoBase * $numeroCuotas), 2);

                foreach ($gestiones as $i => $gestion) {
                    $montoCuota = ($i === $numeroCuotas - 1) ? round($montoBase + $residuo, 2) : $montoBase;
                    $gestion->update(['monto_cuota' => $montoCuota]);
                }
            }

            Cliente::recalcularSaldo($venta->cliente_id);
        });
    }
}
