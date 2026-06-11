<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\PagoVenta;
use App\Models\GestionCobro;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagosYUbicacionController extends Controller
{
    public function index()
    {
        $sucursalId = auth()->user()?->sucursal_id ?? 1;
        $clientes = Cliente::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre_completo . (filled($c->dui) ? " ({$c->dui})" : ''),
                'dui' => $c->dui,
            ]);

        return view('pagos-y-ubicacion.form', ['clientes' => $clientes]);
    }

    public function procesar(Request $request)
    {
        $resultado = [
            'pagos_procesados' => 0,
            'pagos_errores' => 0,
            'ubicaciones_actualizadas' => 0,
            'ubicaciones_errores' => 0,
            'detalles' => [],
        ];

        $sucursalId = auth()->user()?->sucursal_id ?? 1;

        // ── PROCESAR PAGOS ────────────────────────────────────────────────
        foreach ($request->input('pagos', []) as $pago) {
            try {
                $cliente = Cliente::find($pago['cliente_id']);

                if (!$cliente || $cliente->sucursal_id != $sucursalId) {
                    throw new \Exception("Cliente no encontrado.");
                }

                $monto          = (float) $pago['monto_pago'];
                $fecha          = $pago['fecha_pago'];
                $cantidadAbonos = (int) ($pago['cantidad_abonos'] ?? 1);

                // ── Validación básica ─────────────────────────────────────────
                if ($monto <= 0) {
                    throw new \Exception("El monto debe ser mayor a \$0.");
                }

                // ── Próxima cuota pendiente o parcial ─────────────────────────
                $cuota = GestionCobro::where('cliente_id', $cliente->id)
                    ->whereIn('estado', ['parcialmente_cobrado', 'pendiente'])
                    ->orderBy('fecha_vencimiento')
                    ->first();

                if (!$cuota) {
                    throw new \Exception("No hay cuotas pendientes para este cliente.");
                }

                $venta       = $cuota->venta;
                $saldoActual = (float) $venta->saldo_pendiente;

                // ── Validar que no se pague más de lo que debe ────────────────
                if ($saldoActual <= 0) {
                    throw new \Exception(
                        "La venta ya está completamente pagada (total \$" . number_format($venta->total, 2) . ")."
                    );
                }

                if ($monto > $saldoActual) {
                    throw new \Exception(
                        "El monto \$" . number_format($monto, 2) .
                        " supera el saldo pendiente \$" . number_format($saldoActual, 2) .
                        ". Máximo permitido: \$" . number_format($saldoActual, 2) . "."
                    );
                }

                // ── Registrar el pago ─────────────────────────────────────────
                PagoVenta::create([
                    'venta_id'      => $venta->id,
                    'cliente_id'    => $cliente->id,
                    'user_id'       => auth()->id(),
                    'monto'         => $monto,
                    'fecha_pago'    => Carbon::parse($fecha),
                    'metodo_pago'   => 'efectivo',
                    'observaciones' => "Ingreso masivo: {$cantidadAbonos} abono(s).",
                ]);

                // ── Actualizar totales de la venta ────────────────────────────
                $venta->monto_pagado    = round($venta->monto_pagado + $monto, 2);
                $venta->saldo_pendiente = round($saldoActual - $monto, 2);

                // ── Distribuir el pago en cuotas ──────────────────────────────
                $montoRestante = $monto;

                $cuotasPorPagar = GestionCobro::where('venta_id', $venta->id)
                    ->whereIn('estado', ['parcialmente_cobrado', 'pendiente'])
                    ->orderBy('fecha_vencimiento')
                    ->take($cantidadAbonos + 1)
                    ->get();

                foreach ($cuotasPorPagar as $c) {
                    if ($montoRestante <= 0) break;

                    $faltaPorPagar   = round($c->monto_cuota - $c->monto_pagado, 2);
                    $pagoC           = min($faltaPorPagar, $montoRestante);
                    $c->monto_pagado = round($c->monto_pagado + $pagoC, 2);
                    $montoRestante   = round($montoRestante - $pagoC, 2);

                    $c->estado = $c->monto_pagado >= $c->monto_cuota
                        ? 'cobrado'
                        : 'parcialmente_cobrado';
                    $c->save();
                }

                // ── Marcar venta como completada si saldo llegó a 0 ──────────
                if ($venta->saldo_pendiente <= 0) {
                    $venta->saldo_pendiente = 0;
                    $venta->estado          = 'completada';

                    // Cerrar cuotas que queden abiertas
                    GestionCobro::where('venta_id', $venta->id)
                        ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                        ->update([
                            'estado'       => 'cobrado',
                            'monto_pagado' => DB::raw('monto_cuota'),
                        ]);
                }

                $venta->save();

                $resultado['pagos_procesados']++;
                $resultado['detalles'][] = [
                    'tipo'    => 'pago',
                    'estado'  => 'ok',
                    'cliente' => $cliente->nombre_completo,
                    'dui'     => $cliente->dui,
                    'monto'   => "\$" . number_format($monto, 2),
                    'saldo'   => "\$" . number_format($venta->saldo_pendiente, 2),
                ];

            } catch (\Throwable $e) {
                $resultado['pagos_errores']++;
                $resultado['detalles'][] = [
                    'tipo' => 'pago',
                    'estado' => 'error',
                    'cliente' => '?',
                    'dui' => '?',
                    'motivo' => $e->getMessage(),
                ];
            }
        }


        return view('pagos-y-ubicacion.resultado', ['resultado' => $resultado]);
    }
}
