<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class EliminarPagoVentaService
{
    /**
     * Elimina uno o más pagos (p.ej. los agrupados en una sola fila del
     * Resumen del Día) y re-sincroniza venta, cuotas y saldo del cliente
     * para que queden como si el pago nunca se hubiera registrado.
     *
     * @param  int[]  $pagoVentaIds
     * @return array{cantidad: int, monto_total: float}
     */
    public static function eliminar(array $pagoVentaIds, ?int $eliminadoPorUserId, ?string $motivo = null): array
    {
        return DB::transaction(function () use ($pagoVentaIds, $eliminadoPorUserId, $motivo) {
            $pagos = PagoVenta::with('cliente:id,nombre,apellido', 'venta:id,numero_venta')
                ->whereIn('id', $pagoVentaIds)
                ->get();

            if ($pagos->isEmpty()) {
                throw new \RuntimeException('El pago ya no existe o fue eliminado por otro usuario.');
            }

            $ventaIds = $pagos->pluck('venta_id')->unique()->filter();
            $clienteIds = $pagos->pluck('cliente_id')->unique()->filter();
            $montoTotal = (float) $pagos->sum('monto');

            $detalle = $pagos->map(fn (PagoVenta $p) => [
                'id' => $p->id,
                'venta_id' => $p->venta_id,
                'numero_venta' => $p->venta?->numero_venta,
                'cliente_id' => $p->cliente_id,
                'cliente_nombre' => $p->cliente?->nombre_completo,
                'monto' => (float) $p->monto,
                'metodo_pago' => $p->metodo_pago,
                'fecha_pago' => optional($p->fecha_pago)->toDateString(),
            ])->all();

            foreach ($pagos as $pago) {
                $pago->delete();
            }

            foreach ($ventaIds as $ventaId) {
                self::resincronizarCuotas((int) $ventaId);
            }

            foreach ($clienteIds as $clienteId) {
                Cliente::recalcularSaldo($clienteId);
            }

            activity('pagos_eliminados')
                ->causedBy($eliminadoPorUserId ? \App\Models\User::find($eliminadoPorUserId) : null)
                ->withProperties([
                    'pagos_eliminados' => $detalle,
                    'monto_total' => $montoTotal,
                    'motivo' => $motivo,
                ])
                ->log(sprintf(
                    'Eliminó %d pago(s) por $%s desde el Resumen del Día%s',
                    $pagos->count(),
                    number_format($montoTotal, 2),
                    $motivo ? " — Motivo: {$motivo}" : ''
                ));

            return [
                'cantidad' => $pagos->count(),
                'monto_total' => $montoTotal,
            ];
        });
    }

    /**
     * Redistribuye lo realmente pagado de una venta (tras eliminar pagos)
     * entre sus cuotas, en el mismo orden (FIFO por numero_cuota) en que
     * CobroController las fue aplicando originalmente.
     */
    private static function resincronizarCuotas(int $ventaId): void
    {
        $venta = Venta::where('id', $ventaId)->lockForUpdate()->first();
        if (! $venta) {
            return;
        }

        $totalPagadoCuotas = max(0, (float) $venta->monto_pagado - (float) $venta->prima);

        $cuotas = GestionCobro::where('venta_id', $ventaId)
            ->orderBy('numero_cuota')
            ->get();

        $restante = $totalPagadoCuotas;

        foreach ($cuotas as $cuota) {
            $aplicado = min($restante, (float) $cuota->monto_cuota);
            $estado = $aplicado <= 0
                ? 'pendiente'
                : ($aplicado >= (float) $cuota->monto_cuota ? 'cobrado' : 'parcialmente_cobrado');

            $cuota->update([
                'monto_pagado' => round($aplicado, 2),
                'estado' => $estado,
            ]);

            $restante = round($restante - $aplicado, 2);
        }
    }
}
