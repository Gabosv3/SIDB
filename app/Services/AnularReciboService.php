<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\PagoVenta;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class AnularReciboService
{
    /**
     * Anula todos los pagos de un numero_recibo (una sola visita puede haber
     * repartido el abono en varias cuotas, todas comparten el mismo recibo).
     * Los registros NUNCA se borran — solo dejan de contar en los saldos y
     * cuotas de la venta, quedando marcados con quién y por qué se anularon.
     *
     * @return array{numero_recibo: string, cantidad: int, monto_total: float}
     */
    public static function anular(string $numeroRecibo, int $anuladoPorUserId, ?string $motivo = null): array
    {
        return DB::transaction(function () use ($numeroRecibo, $anuladoPorUserId, $motivo) {
            $pagos = PagoVenta::with('cliente:id,nombre,apellido', 'venta:id,numero_venta')
                ->where('numero_recibo', $numeroRecibo)
                ->whereNull('anulado_en')
                ->get();

            if ($pagos->isEmpty()) {
                throw new \RuntimeException('Ese recibo no existe o ya fue anulado.');
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

            PagoVenta::whereIn('id', $pagos->pluck('id'))->update([
                'anulado_en' => now(),
                'anulado_por' => $anuladoPorUserId,
                'motivo_anulacion' => $motivo,
            ]);

            foreach ($ventaIds as $ventaId) {
                self::resincronizarVenta((int) $ventaId);
            }

            foreach ($clienteIds as $clienteId) {
                Cliente::recalcularSaldo((int) $clienteId);
            }

            activity('recibo_anulado')
                ->causedBy(User::find($anuladoPorUserId))
                ->withProperties([
                    'numero_recibo' => $numeroRecibo,
                    'pagos' => $detalle,
                    'monto_total' => $montoTotal,
                    'motivo' => $motivo,
                ])
                ->log(sprintf(
                    'Anuló el recibo %s por $%s%s',
                    $numeroRecibo,
                    number_format($montoTotal, 2),
                    $motivo ? " — Motivo: {$motivo}" : ''
                ));

            return [
                'numero_recibo' => $numeroRecibo,
                'cantidad' => $pagos->count(),
                'monto_total' => $montoTotal,
            ];
        });
    }

    /**
     * Vuelve a calcular monto_pagado/saldo_pendiente/estado de la venta
     * excluyendo los pagos anulados, y re-sincroniza sus cuotas.
     */
    private static function resincronizarVenta(int $ventaId): void
    {
        $venta = Venta::where('id', $ventaId)->lockForUpdate()->first();

        if (! $venta) {
            return;
        }

        $totalPagado = round((float) $venta->prima + (float) $venta->pagos()->whereNull('anulado_en')->sum('monto'), 2);
        $venta->monto_pagado = $totalPagado;
        $venta->saldo_pendiente = max(0, round((float) $venta->total - $totalPagado, 2));
        if ($venta->saldo_pendiente > 0 && $venta->estado === 'completada') {
            $venta->estado = 'pendiente';
        }
        $venta->save();

        EliminarPagoVentaService::resincronizarCuotas($ventaId);
    }
}
