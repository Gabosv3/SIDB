<?php

namespace App\Observers;

use App\Models\DetalleCompra;

class DetalleCompraObserver
{
    /**
     * Calcular subtotal después de guardar
     */
    public function saving(DetalleCompra $detalle): void
    {
        $detalle->subtotal = $detalle->calcularSubtotal();
    }

    /**
     * Actualizar totales de la compra padre
     */
    public function saved(DetalleCompra $detalle): void
    {
        $this->actualizarTotalesCompra($detalle);
    }

    /**
     * Actualizar totales cuando se elimina un detalle
     */
    public function deleted(DetalleCompra $detalle): void
    {
        $this->actualizarTotalesCompra($detalle);
    }

    /**
     * Actualizar totales de la compra
     */
    private function actualizarTotalesCompra(DetalleCompra $detalle): void
    {
        $compra = $detalle->compra;
        if (!$compra) {
            return;
        }

        $subtotal = $compra->detalles()->sum('subtotal');
        $descuentoMonto = $compra->descuento_monto ?? 0;
        $impuestoPorcentaje = $compra->impuesto_porcentaje ?? 0;

        $subtotalConDescuento = $subtotal - $descuentoMonto;
        $impuestoMonto = ($subtotalConDescuento * $impuestoPorcentaje) / 100;
        $total = $subtotalConDescuento + $impuestoMonto;

        $compra->update([
            'subtotal' => $subtotal,
            'impuesto_monto' => $impuestoMonto,
            'total' => $total,
            'saldo_pendiente' => $total,
        ]);
    }
}
