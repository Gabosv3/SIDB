<?php

namespace App\Observers;

use App\Models\PagoCompra;

class PagoCompraObserver
{
    /**
     * Actualizar saldo pendiente al registrar pago
     */
    public function created(PagoCompra $pago): void
    {
        $compra = $pago->compra;
        $compra->reducirSaldoPendiente($pago->monto);

        // Si está completamente pagado, cambiar estado
        if ($compra->estaPagada() && $compra->estado !== 'completada') {
            $compra->update(['estado' => 'completada']);
        }

        \Log::info("Pago registrado de {$pago->monto} para compra {$compra->numero_compra}");
    }

    /**
     * Actualizar saldo cuando se elimina un pago
     */
    public function deleted(PagoCompra $pago): void
    {
        $compra = $pago->compra;
        $compra->incrementarSaldoPendiente($pago->monto);

        // Cambiar estado a pendiente si hay deuda
        if ($compra->saldo_pendiente > 0 && $compra->estado === 'completada') {
            $compra->update(['estado' => 'pendiente']);
        }

        \Log::info("Pago eliminado de {$pago->monto} para compra {$compra->numero_compra}");
    }
}
