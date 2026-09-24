<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Lógica compartida para corregir una venta ya creada (producto equivocado,
// prima mal puesta, cliente equivocado) — usada tanto por el endpoint que
// llama la app del vendedor (PATCH /ventas/{id}, mismo día, sin abonos
// todavía) como por la acción "Corregir venta" del panel (sin límite de
// tiempo, para cuando ya pasó el día o ya tiene abonos y hace falta un
// administrador). Vivir en un solo lugar evita que la lógica de precios de
// crédito se rompa por estar duplicada en varios sitios, como ya pasó antes.
class VentaCorreccionService
{
    // Procesa las líneas de detalle (precios, cuotas, subtotal) y calcula los
    // totales de la venta. $asignacionDetalles puede venir vacía (colección
    // vacía) cuando no aplica revisar la asignación diaria — en ese caso las
    // líneas de contado usan el precio_unitario que venga en el input.
    public static function calcularDetallesYTotales(array $detallesInput, float $descuentoPct, float $prima, Collection $asignacionDetalles): array
    {
        $subtotal     = 0;
        $detallesPrep = [];

        foreach ($detallesInput as $item) {
            $detalleAsignado = $asignacionDetalles->get((int) $item['producto_id']);
            $tipoPagoLinea    = $item['tipo_pago'] ?? null;
            $precioCuota      = null;

            if ($tipoPagoLinea === 'credito' && isset($item['cuotas'])) {
                $producto     = Producto::find($item['producto_id']);
                $planCatalogo = collect($producto?->precios_cuotas ?? [])
                    ->first(fn ($p) => (int) ($p['cuotas'] ?? 0) === (int) $item['cuotas']);

                $precioCuota = $planCatalogo
                    ? (float) ($planCatalogo['precio_cuota'] ?? $planCatalogo['precio'] ?? 0)
                    : (float) ($item['precio_cuota'] ?? 0);

                $precioUnitario = round((int) $item['cuotas'] * $precioCuota, 2);
            } else {
                $precioUnitario = $detalleAsignado?->precio_venta ?? $item['precio_unitario'];
            }

            $dto   = (float) ($item['descuento_porcentaje'] ?? 0);
            $linea = round($item['cantidad'] * $precioUnitario * (1 - $dto / 100), 2);
            $subtotal += $linea;

            if (isset($item['cuotas']) && $precioCuota === null) {
                $precioCuota = $linea / $item['cuotas'];
            }

            $detallesPrep[] = [
                'producto_id'          => $item['producto_id'],
                'cantidad'             => $item['cantidad'],
                'precio_unitario'      => $precioUnitario,
                'descuento_porcentaje' => $dto,
                'subtotal'             => $linea,
                'tipo_pago'            => $tipoPagoLinea,
                'cuotas'               => $item['cuotas'] ?? null,
                'precio_cuota'         => $precioCuota !== null ? round($precioCuota, 2) : null,
            ];
        }

        $descuentoMonto = round($subtotal * $descuentoPct / 100, 2);
        $total          = round($subtotal - $descuentoMonto, 2);

        $tiposLinea = collect($detallesPrep)->pluck('tipo_pago')->filter()->unique()->values();
        $tipoPagoVenta = $tiposLinea->count() > 1 ? 'mixta' : ($tiposLinea->first() ?? 'contado');

        $detallesPrep = array_map(function ($d) use ($tipoPagoVenta) {
            if ($d['tipo_pago'] === null) {
                $d['tipo_pago'] = $tipoPagoVenta === 'mixta' ? 'contado' : $tipoPagoVenta;
            }
            return $d;
        }, $detallesPrep);

        $totalContado = collect($detallesPrep)->where('tipo_pago', 'contado')->sum('subtotal');
        $totalCredito = collect($detallesPrep)->where('tipo_pago', 'credito')->sum('subtotal');

        $montoPagado    = round($totalContado + $prima, 2);
        $saldoPendiente = max(0, round($totalCredito - $prima, 2));

        return compact('detallesPrep', 'subtotal', 'descuentoMonto', 'total', 'tipoPagoVenta', 'montoPagado', 'saldoPendiente', 'totalCredito');
    }

    /**
     * Aplica la corrección completa a una venta ya creada: revierte y vuelve
     * a aplicar el descuento de la asignación diaria (si se pasa una), borra
     * y recrea los detalles y las cuotas de cobro, y recalcula el saldo del
     * cliente. Devuelve ['error' => string] o ['venta' => Venta].
     *
     * @param  Collection|null  $asignacion  AsignacionDiaria del vendedor para hoy, o null si no aplica
     *                                        validar/descontar stock (ej. corrección desde el panel de una
     *                                        venta de un día anterior).
     */
    public static function aplicarCorreccion(
        Venta $venta,
        array $nuevosDetalles,
        float $prima,
        float $descuentoPct,
        int $clienteId,
        ?\App\Models\AsignacionDiaria $asignacion,
        string $motivo,
        ?int $ventaIdExcluirDeValidacion = null,
        bool $forzar = false,
    ): array {
        return DB::transaction(function () use ($venta, $nuevosDetalles, $prima, $descuentoPct, $clienteId, $asignacion, $motivo, $ventaIdExcluirDeValidacion, $forzar) {
            if (in_array($venta->estado, ['cancelada', 'devuelta'], true)) {
                return ['error' => 'Esta venta está anulada y no se puede corregir.'];
            }

            $hayAbonosPosteriores = PagoVenta::where('venta_id', $venta->id)->whereNull('anulado_en')->exists();
            if ($hayAbonosPosteriores && ! $forzar) {
                return ['error' => 'Esta venta ya tiene abonos registrados aparte de la prima inicial. Corregir los productos o la prima ahora dejaría esos abonos inconsistentes con las cuotas nuevas — hay que resolverlo manualmente.'];
            }

            // Forzado con abonos ya registrados: el dinero recibido (PagoVenta)
            // no se toca ni se pierde, pero las cuotas (GestionCobro) se
            // recrean desde cero más abajo -- hay que cuadrar a mano cuánto
            // de lo ya cobrado corresponde a cada cuota nueva.
            if ($hayAbonosPosteriores && $forzar) {
                $motivo = '[FORZADA CON ABONOS YA REGISTRADOS] ' . $motivo;
            }

            $asignacionDetalles = $asignacion ? $asignacion->detalles->keyBy('producto_id') : collect();

            if ($asignacion) {
                // Revertir lo que esta venta ya había descontado, antes de
                // revalidar — si no, se contaría dos veces a sí misma.
                foreach ($venta->detalles as $detalleViejo) {
                    $detalleAsignado = $asignacionDetalles->get($detalleViejo->producto_id);
                    if ($detalleAsignado) {
                        $detalleAsignado->decrement('cantidad_vendida', $detalleViejo->cantidad);
                        $detalleAsignado->refresh();
                        $detalleAsignado->update([
                            'cantidad_devuelta' => max(0, $detalleAsignado->cantidad_asignada - $detalleAsignado->cantidad_vendida),
                        ]);
                    }
                }

                $cantidadesSolicitadas = collect($nuevosDetalles)->groupBy('producto_id')->map(fn ($items) => $items->sum('cantidad'));
                foreach ($cantidadesSolicitadas as $productoId => $cantidadSolicitada) {
                    $detalleAsignado = $asignacionDetalles->get((int) $productoId);
                    if (! $detalleAsignado) {
                        return ['error' => 'Uno de los productos no está incluido en la asignación diaria activa.'];
                    }
                    $cantidadVendidaHoy = DetalleVenta::where('producto_id', $productoId)
                        ->whereHas('venta', function ($query) use ($asignacion, $ventaIdExcluirDeValidacion) {
                            $query->where('vendedor_id', $asignacion->vendedor_id)
                                ->whereDate('fecha_venta', today())
                                ->whereIn('estado', ['pendiente', 'completada'])
                                ->when($ventaIdExcluirDeValidacion, fn ($q) => $q->where('id', '!=', $ventaIdExcluirDeValidacion));
                        })
                        ->sum('cantidad');
                    if (($cantidadVendidaHoy + $cantidadSolicitada) > $detalleAsignado->cantidad_asignada) {
                        return ['error' => "La cantidad solicitada supera lo asignado para el producto {$detalleAsignado->producto_id}."];
                    }
                }
            }

            [
                'detallesPrep'   => $detallesPrep,
                'subtotal'       => $subtotal,
                'descuentoMonto' => $descuentoMonto,
                'total'          => $total,
                'tipoPagoVenta'  => $tipoPagoVenta,
                'montoPagado'    => $montoPagado,
                'saldoPendiente' => $saldoPendiente,
            ] = self::calcularDetallesYTotales($nuevosDetalles, $descuentoPct, $prima, $asignacionDetalles);

            if ($saldoPendiente > 0) {
                $cliente = Cliente::find($clienteId);
                if ($cliente && (float) $cliente->limite_credito > 0) {
                    $saldoSinEstaVenta = (float) $cliente->saldo - (float) $venta->saldo_pendiente;
                    $saldoResultante   = $saldoSinEstaVenta + $saldoPendiente;
                    if ($saldoResultante > (float) $cliente->limite_credito) {
                        return ['error' => sprintf(
                            'El cliente supera su límite de crédito ($%s) con esta corrección.',
                            number_format((float) $cliente->limite_credito, 2)
                        )];
                    }
                }
            }

            $viejoClienteId = $venta->cliente_id;
            $estaCompletada = $saldoPendiente <= 0;

            $venta->update([
                'cliente_id'           => $clienteId,
                'tipo_pago'            => $tipoPagoVenta,
                'prima'                => $prima,
                'subtotal'             => $subtotal,
                'descuento_porcentaje' => $descuentoPct,
                'descuento_monto'      => $descuentoMonto,
                'total'                => $total,
                'monto_pagado'         => $montoPagado,
                'saldo_pendiente'      => $saldoPendiente,
                'estado'               => $estaCompletada ? 'completada' : 'pendiente',
                'observaciones'        => trim(($venta->observaciones ? $venta->observaciones . ' | ' : '') . 'Corregida: ' . $motivo),
            ]);

            $venta->detalles()->delete();
            GestionCobro::where('venta_id', $venta->id)->delete();

            foreach ($detallesPrep as $d) {
                DetalleVenta::create(array_merge(['venta_id' => $venta->id], $d));

                $detalleAsignado = $asignacionDetalles->get((int) $d['producto_id']);
                if ($detalleAsignado) {
                    $detalleAsignado->increment('cantidad_vendida', $d['cantidad']);
                    $detalleAsignado->refresh();
                    $detalleAsignado->update([
                        'cantidad_devuelta' => max(0, $detalleAsignado->cantidad_asignada - $detalleAsignado->cantidad_vendida),
                    ]);
                }
            }

            $lineasCredito = collect($detallesPrep)->where('tipo_pago', 'credito');
            if ($lineasCredito->isNotEmpty() && $saldoPendiente > 0) {
                $numeroCuotas = $lineasCredito->filter(fn ($d) => $d['cuotas'])->first()['cuotas']
                    ?? $lineasCredito->first()['cuotas']
                    ?? 1;

                $montoBase = floor($saldoPendiente / $numeroCuotas * 100) / 100;
                $residuo   = round($saldoPendiente - ($montoBase * $numeroCuotas), 2);

                $gestiones = [];
                for ($i = 1; $i <= $numeroCuotas; $i++) {
                    $gestiones[] = [
                        'venta_id'          => $venta->id,
                        'cliente_id'        => $clienteId,
                        'numero_cuota'      => $i,
                        'total_cuotas'      => $numeroCuotas,
                        'monto_cuota'       => $i === $numeroCuotas ? round($montoBase + $residuo, 2) : $montoBase,
                        'monto_pagado'      => 0,
                        'fecha_vencimiento' => now()->addMonths($i)->toDateString(),
                        'estado'            => 'pendiente',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }

                GestionCobro::insert($gestiones);
            }

            Cliente::recalcularSaldo($clienteId);
            if ($viejoClienteId !== $clienteId) {
                Cliente::recalcularSaldo($viejoClienteId);
            }

            return ['venta' => $venta->fresh()->load([
                'detalles.producto:id,nombre,codigo',
                'vendedor:id,nombre,apellido',
                'user:id,name',
            ])];
        });
    }
}
