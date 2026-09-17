<?php

namespace App\Services;

use App\Models\AsignacionDiaria;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Cancela una venta desde el panel administrativo y reintegra lo vendido:
 * si el producto salió de una asignación diaria todavía activa (el vendedor
 * sigue con esa asignación en la calle), se le resta de "vendido" para que
 * lo pueda volver a vender ese mismo día; si no (la asignación ya se
 * liquidó, o la venta nunca vino de una asignación), se reintegra al stock
 * general del producto -- exactamente el mismo criterio que ya usa
 * DetalleVenta al decidir si descuenta stock general o no al crear la venta.
 */
class AnularVentaService
{
    /** @return array{ok?: bool, error?: string} */
    public static function anular(Venta $venta, string $motivo): array
    {
        if (in_array($venta->estado, ['cancelada', 'devuelta'], true)) {
            return ['error' => 'Esta venta ya está cancelada o devuelta.'];
        }

        if ((float) $venta->monto_pagado > 0) {
            return ['error' => 'Esta venta ya tiene pagos registrados. Elimina o anula esos pagos primero antes de cancelar la venta.'];
        }

        DB::transaction(function () use ($venta, $motivo) {
            $venta->loadMissing('detalles.producto');

            foreach ($venta->detalles as $detalle) {
                $asignacion = $venta->vendedor_id
                    ? AsignacionDiaria::with('detalles')
                        ->where('vendedor_id', $venta->vendedor_id)
                        ->whereDate('fecha', $venta->fecha_venta)
                        ->where('estado', 'activa')
                        ->whereHas('detalles', fn ($q) => $q->where('producto_id', $detalle->producto_id))
                        ->first()
                    : null;

                if ($asignacion) {
                    $detalleAsignado = $asignacion->detalles->firstWhere('producto_id', $detalle->producto_id);
                    $detalleAsignado->decrement('cantidad_vendida', $detalle->cantidad);
                    $detalleAsignado->refresh();
                    $detalleAsignado->update([
                        'cantidad_devuelta' => max(0, $detalleAsignado->cantidad_asignada - $detalleAsignado->cantidad_vendida),
                    ]);
                } else {
                    self::ajustarStock($detalle->producto, $detalle->cantidad);
                }
            }

            $venta->update([
                'estado' => 'cancelada',
                'observaciones' => trim(($venta->observaciones ? $venta->observaciones.' | ' : '').'Cancelada desde el panel: '.$motivo),
            ]);
        });

        return ['ok' => true];
    }

    /** Igual que DetalleVenta::ajustarStock() -- un combo ajusta sus componentes, no el combo mismo. */
    private static function ajustarStock(?Producto $producto, int $delta): void
    {
        if (! $producto) {
            return;
        }

        if (! $producto->es_combo) {
            $producto->increment('stock', $delta);

            return;
        }

        foreach ($producto->componentes as $componente) {
            $componente->componente?->increment('stock', $delta * $componente->cantidad);
        }
    }
}
