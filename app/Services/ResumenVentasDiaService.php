<?php

namespace App\Services;

use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResumenVentasDiaService
{
    /**
     * Ventas del día, con el cliente/vendedor cargados y marcadas si el
     * cliente era nuevo (su primera compra alguna vez) o ya existía.
     *
     * @param  array<int>  $vendedorIds
     */
    public static function resumen(string $fecha, array $vendedorIds = [], string $buscarCliente = ''): Collection
    {
        $dia = Carbon::parse($fecha)->startOfDay();

        $query = Venta::whereDate('fecha_venta', $dia)
            ->with(['cliente', 'vendedor', 'user'])
            ->when($vendedorIds !== [], fn ($q) => $q->whereIn('vendedor_id', $vendedorIds))
            ->orderBy('fecha_venta');

        $ventas = $query->get();

        $termino = mb_strtolower(trim($buscarCliente));
        if ($termino !== '') {
            $ventas = $ventas->filter(function (Venta $v) use ($termino) {
                $c = $v->cliente;
                if (! $c) {
                    return false;
                }

                return str_contains(mb_strtolower($c->nombre_completo ?? ''), $termino)
                    || str_contains(mb_strtolower((string) $c->codigo_anterior), $termino)
                    || str_contains(mb_strtolower((string) $c->telefono_normal), $termino);
            })->values();
        }

        $clienteIds = $ventas->pluck('cliente_id')->filter()->unique()->values();

        // Clientes que ya tenían al menos una venta ANTES de este día (en
        // cualquier estado — si ya se le vendió antes, no es "nuevo").
        $clientesConHistorial = $clienteIds->isEmpty()
            ? collect()
            : Venta::whereIn('cliente_id', $clienteIds)
                ->where('fecha_venta', '<', $dia)
                ->distinct()
                ->pluck('cliente_id');

        return $ventas->map(function (Venta $v) use ($clientesConHistorial) {
            return (object) [
                'venta' => $v,
                'es_cliente_nuevo' => $v->cliente_id && ! $clientesConHistorial->contains($v->cliente_id),
            ];
        })->values();
    }

    /** @param  Collection<int, object{venta: Venta, es_cliente_nuevo: bool}>  $resumen */
    public static function totales(Collection $resumen): array
    {
        $validas = $resumen->reject(fn ($r) => $r->venta->estado === 'cancelada');

        return [
            'total_vendido' => (float) $validas->sum(fn ($r) => (float) $r->venta->total),
            'total_ventas' => $validas->count(),
            'clientes_nuevos' => $resumen->filter(fn ($r) => $r->es_cliente_nuevo)->pluck('venta.cliente_id')->unique()->count(),
            'clientes_recurrentes' => $resumen->reject(fn ($r) => $r->es_cliente_nuevo)->pluck('venta.cliente_id')->filter()->unique()->count(),
            'ventas_canceladas' => $resumen->filter(fn ($r) => in_array($r->venta->estado, ['cancelada', 'devuelta'], true))->count(),
        ];
    }
}
