<?php

namespace App\Services;

use App\Models\Cobrador;
use App\Models\PagoVenta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MoverPagoVentaService
{
    /**
     * Cambia el cobrador y/o la fecha de uno o más pagos (p.ej. los agrupados en
     * una sola fila del Resumen del Día). No toca cuotas, saldos ni el número de
     * recibo: el cobro sigue siendo el mismo, solo cambia a quién y a qué día se
     * le atribuye en el Resumen de Cobros, el Cuadre de Caja y las liquidaciones.
     *
     * @param  int[]  $pagoVentaIds
     * @return array{cantidad: int, monto_total: float}
     */
    public static function mover(array $pagoVentaIds, int $cobradorId, string $fecha, ?int $movidoPorUserId, ?string $motivo = null): array
    {
        $cobrador = Cobrador::where('activo', true)->find($cobradorId);

        if (! $cobrador?->user_id) {
            throw new \RuntimeException('Ese cobrador no está activo o no tiene un usuario del sistema vinculado.');
        }

        $nuevaFecha = Carbon::parse($fecha)->startOfDay();

        if ($nuevaFecha->gt(today())) {
            throw new \RuntimeException('La fecha no puede ser futura.');
        }

        return DB::transaction(function () use ($pagoVentaIds, $cobrador, $nuevaFecha, $movidoPorUserId, $motivo) {
            $pagos = PagoVenta::with('cliente:id,nombre,apellido', 'venta:id,numero_venta,fecha_venta')
                ->whereIn('id', $pagoVentaIds)
                ->whereNull('anulado_en')
                ->lockForUpdate()
                ->get();

            if ($pagos->isEmpty()) {
                throw new \RuntimeException('El pago ya no existe, fue anulado o lo eliminó otro usuario.');
            }

            foreach ($pagos as $pago) {
                if ($pago->venta?->fecha_venta && $nuevaFecha->lt(Carbon::parse($pago->venta->fecha_venta)->startOfDay())) {
                    throw new \RuntimeException(sprintf(
                        'La fecha no puede ser anterior a la venta %s (%s).',
                        $pago->venta->numero_venta,
                        Carbon::parse($pago->venta->fecha_venta)->format('d/m/Y')
                    ));
                }
            }

            $detalle = $pagos->map(fn (PagoVenta $p) => [
                'id' => $p->id,
                'numero_recibo' => $p->numero_recibo,
                'cliente_nombre' => $p->cliente?->nombre_completo,
                'numero_venta' => $p->venta?->numero_venta,
                'monto' => (float) $p->monto,
                'user_id_anterior' => $p->user_id,
                'fecha_anterior' => optional($p->fecha_pago)->toDateString(),
            ])->all();

            foreach ($pagos as $pago) {
                $pago->update([
                    'user_id' => $cobrador->user_id,
                    'fecha_pago' => $nuevaFecha->toDateString(),
                ]);
            }

            $montoTotal = (float) $pagos->sum('monto');

            activity('pagos_movidos')
                ->causedBy($movidoPorUserId ? \App\Models\User::find($movidoPorUserId) : null)
                ->withProperties([
                    'pagos_movidos' => $detalle,
                    'monto_total' => $montoTotal,
                    'user_id_nuevo' => $cobrador->user_id,
                    'cobrador_nuevo' => $cobrador->nombre_completo,
                    'fecha_nueva' => $nuevaFecha->toDateString(),
                    'motivo' => $motivo,
                ])
                ->log(sprintf(
                    'Movió %d pago(s) por $%s al cobrador %s con fecha %s%s',
                    $pagos->count(),
                    number_format($montoTotal, 2),
                    $cobrador->nombre_completo,
                    $nuevaFecha->format('d/m/Y'),
                    $motivo ? " — Motivo: {$motivo}" : ''
                ));

            return ['cantidad' => $pagos->count(), 'monto_total' => $montoTotal];
        });
    }
}
