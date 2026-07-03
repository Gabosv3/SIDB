<?php

namespace App\Services;

use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class RegistrarCobroManualService
{
    /**
     * Registra un cobro manual (p.ej. desde el Resumen del Día) distribuyendo
     * el monto entre las cuotas pendientes de la venta, en el mismo orden
     * (fecha_vencimiento, numero_cuota) que usa la app móvil de cobradores.
     *
     * @param  array{venta_id:int, monto:float, metodo_pago:string, referencia:?string, observaciones:?string, fecha_pago:string, user_id:int}  $data
     * @return array{monto: float, cuotas_aplicadas: int}
     */
    public static function registrar(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Bloquea la fila para que dos cobros concurrentes sobre la misma
            // venta no lean el mismo saldo_pendiente y la sobre-paguen.
            $venta = Venta::where('id', $data['venta_id'])->lockForUpdate()->firstOrFail();
            $monto = (float) $data['monto'];

            if ($monto > (float) $venta->saldo_pendiente) {
                throw new \RuntimeException(sprintf(
                    'El monto $%s supera el saldo pendiente de esta venta ($%s).',
                    number_format($monto, 2),
                    number_format((float) $venta->saldo_pendiente, 2)
                ));
            }

            $gestiones = GestionCobro::where('venta_id', $venta->id)
                ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                ->orderBy('fecha_vencimiento')
                ->orderBy('numero_cuota')
                ->get();

            $restante = $monto;
            $cuotasAplicadas = 0;

            foreach ($gestiones as $gestion) {
                if ($restante <= 0) {
                    break;
                }

                $saldoCuota = round((float) $gestion->monto_cuota - (float) $gestion->monto_pagado, 2);
                $aplicar = min($restante, $saldoCuota);

                PagoVenta::create([
                    'venta_id' => $venta->id,
                    'cliente_id' => $venta->cliente_id,
                    'monto' => $aplicar,
                    'fecha_pago' => $data['fecha_pago'],
                    'metodo_pago' => $data['metodo_pago'],
                    'referencia' => $data['referencia'] ?? null,
                    'observaciones' => $data['observaciones'] ?? null,
                    'user_id' => $data['user_id'],
                ]);

                $gestion->increment('monto_pagado', $aplicar);
                $gestion->refresh();
                $gestion->update([
                    'estado' => $gestion->monto_pagado >= $gestion->monto_cuota ? 'cobrado' : 'parcialmente_cobrado',
                ]);

                $restante = round($restante - $aplicar, 2);
                $cuotasAplicadas++;
            }

            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'venta_id' => $venta->id,
                    'cliente_id' => $venta->cliente_id,
                    'monto' => $monto,
                    'metodo_pago' => $data['metodo_pago'],
                    'fecha_pago' => $data['fecha_pago'],
                    'atribuido_a_user_id' => $data['user_id'],
                ])
                ->log(sprintf(
                    'Registró un cobro manual de $%s en la venta %s desde el Resumen del Día',
                    number_format($monto, 2),
                    $venta->numero_venta
                ));

            return ['monto' => $monto, 'cuotas_aplicadas' => $cuotasAplicadas];
        });
    }
}
