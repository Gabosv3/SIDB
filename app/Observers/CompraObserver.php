<?php

namespace App\Observers;

use App\Models\Compra;
use App\Models\MovimientoStock;

class CompraObserver
{
    /**
     * Ejecutarse después de crear una compra
     */
    public function created(Compra $compra): void
    {
        // Registrar la compra en el auditoría
        \Log::info("Nueva compra creada: {$compra->numero_compra}");
    }

    /**
     * Ejecutarse cuando se actualiza el estado de la compra
     */
    public function updated(Compra $compra): void
    {
        // Si la compra cambió a recibida, actualizar stock
        if ($compra->isDirty('estado') && $compra->estado === 'recibida') {
            $this->registrarMovimientoStock($compra);
        }
    }

    /**
     * Registrar movimientos de stock de la compra
     */
    private function registrarMovimientoStock(Compra $compra): void
    {
        foreach ($compra->detalles as $detalle) {
            // Actualizar stock del producto
            $detalle->producto->increment('stock', $detalle->cantidad);

            // Registrar movimiento
            MovimientoStock::create([
                'producto_id' => $detalle->producto_id,
                'tipo_movimiento' => 'entrada',
                'cantidad' => $detalle->cantidad,
                'descripcion' => "Compra {$compra->numero_compra}",
                'referencia_id' => $compra->id,
            ]);
        }

        \Log::info("Stock actualizado para compra: {$compra->numero_compra}");
    }

    /**
     * Ejecutarse antes de eliminar una compra
     */
    public function deleting(Compra $compra): void
    {
        // Prevenir eliminación de compras completadas si es necesario
        if ($compra->estado === 'completada') {
            \Log::warning("Intento de eliminar compra completada: {$compra->numero_compra}");
        }
    }
}
