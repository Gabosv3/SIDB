<?php

namespace App\Observers;

use App\Models\Compra;

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
     * Cuando la compra pasa a recibida (o directo a completada, que también
     * implica que la mercadería llegó) sube el stock. Ingresar es idempotente:
     * volver a marcar Recibida no duplica lo ya ingresado.
     */
    public function updated(Compra $compra): void
    {
        if (! $compra->isDirty('estado')) {
            return;
        }

        if (in_array($compra->estado, ['recibida', 'completada'], true)) {
            $compra->ingresarStock();
            \Log::info("Stock actualizado para compra: {$compra->numero_compra}");
        } elseif (in_array($compra->estado, ['cancelada', 'devuelta'], true)) {
            $compra->revertirStock($compra->estado);
            \Log::info("Stock revertido para compra {$compra->estado}: {$compra->numero_compra}");
        }
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
