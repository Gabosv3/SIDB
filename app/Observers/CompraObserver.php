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
            // El stock se actualiza solo -- MovimientoStock::boot() ya lo suma
            // al crearse un registro con tipo 'entrada'. Antes se sumaba acá
            // A MANO además, porque el campo 'tipo' se mandaba mal nombrado
            // ('tipo_movimiento') y el hook del modelo nunca disparaba; ahora
            // que el nombre está bien, sumarlo también acá lo duplicaría.

            // Registrar movimiento. user_id y sucursal_id son obligatorios en
            // la tabla (sin default) -- auth()->id() cubre el caso normal (un
            // admin cambia el estado desde el panel); usuario_id de la compra
            // es el respaldo si esto llega a correr fuera de una sesión web
            // (cola, comando, etc.).
            MovimientoStock::create([
                'producto_id' => $detalle->producto_id,
                'user_id' => auth()->id() ?? $compra->usuario_id,
                'sucursal_id' => $detalle->producto->sucursal_id,
                'tipo' => 'entrada',
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'referencia' => $compra->numero_compra,
                'observaciones' => "Compra {$compra->numero_compra}",
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
