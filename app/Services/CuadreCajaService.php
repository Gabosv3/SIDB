<?php

namespace App\Services;

use App\Models\ComisionTramo;
use App\Models\CuadreCaja;
use App\Models\PagoVenta;
use App\Models\User;
use App\Models\Vale;
use App\Models\Vendedor;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cuadre de caja por persona al cierre del día. Lo que cada persona debe
 * entregar = cobros en efectivo + lo que debe entregar por sus ventas al
 * contado (mismo criterio de "A entregar" en Ventas del Día) − gastos del día
 * que descuentan del efectivo (mismo criterio de Cobros del Día: vales con
 * descuenta_cobro_diario, en cualquier estado).
 */
class CuadreCajaService
{
    /** @return Collection<int, array<string, mixed>> una fila por persona con movimiento ese día */
    public static function filas(Carbon $fecha, ?int $sucursalId = null): Collection
    {
        $dia = $fecha->toDateString();

        $cobros = PagoVenta::query()
            ->whereNull('anulado_en')
            ->where('metodo_pago', 'efectivo')
            ->whereDate('fecha_pago', $dia)
            ->when($sucursalId, fn ($q) => $q->whereHas('venta', fn ($v) => $v->withoutGlobalScopes()->where('sucursal_id', $sucursalId)))
            ->selectRaw('user_id, SUM(monto) as total')->groupBy('user_id')->pluck('total', 'user_id');

        $gastos = Vale::withoutGlobalScopes()
            ->whereDate('fecha_gasto', $dia)
            ->where('descuenta_cobro_diario', true)
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->selectRaw('user_id, SUM(monto) as total')->groupBy('user_id')->pluck('total', 'user_id');

        $contado = self::entregaPorVentasContado($dia, $sucursalId);

        $cuadres = CuadreCaja::whereDate('fecha', $dia)->get()->keyBy('user_id');

        $userIds = collect([$cobros->keys(), $gastos->keys(), $contado->keys(), $cuadres->keys()])->flatten()->filter()->unique();
        $nombres = User::whereIn('id', $userIds)->pluck('name', 'id');

        return $userIds->map(function ($uid) use ($cobros, $gastos, $contado, $cuadres, $nombres) {
            $c = round((float) ($cobros[$uid] ?? 0), 2);
            $v = round((float) ($contado[$uid] ?? 0), 2);
            $g = round((float) ($gastos[$uid] ?? 0), 2);
            $guardado = $cuadres->get($uid);

            return [
                'user_id' => (int) $uid,
                'persona' => $nombres[$uid] ?? "Usuario #{$uid}",
                'cobros' => $c,
                'ventas_contado' => $v,
                'gastos' => $g,
                'esperado' => round($c + $v - $g, 2),
                'cuadre' => $guardado,
            ];
        })->sortBy('persona', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /** Efectivo que cada vendedor (por user_id) debe entregar por sus ventas al contado del día. */
    private static function entregaPorVentasContado(string $dia, ?int $sucursalId): Collection
    {
        $tramos = ComisionTramo::orderByDesc('desde')->get();
        $pct = function (float $subtotal) use ($tramos): float {
            $t = $tramos->first(fn ($x) => (float) $x->desde <= $subtotal && ($x->hasta === null || (float) $x->hasta >= $subtotal));

            return $t ? (float) $t->porcentaje : 0.0;
        };

        $ventas = Venta::withoutGlobalScopes()
            ->whereDate('fecha_venta', $dia)
            ->where('tipo_pago', 'contado')
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->whereNotNull('vendedor_id')
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->with('detalles.producto')
            ->get();

        $userDeVendedor = Vendedor::whereIn('id', $ventas->pluck('vendedor_id')->unique())->pluck('user_id', 'id');

        return $ventas->groupBy(fn (Venta $v) => $userDeVendedor[$v->vendedor_id] ?? null)
            ->reject(fn ($g, $uid) => ! $uid)
            ->map(fn ($g) => round((float) $g->sum(function (Venta $v) use ($pct) {
                $porProductos = (float) $v->detalles->sum(fn ($d) => $d->cantidad * (float) ($d->producto?->precio_vendedor ?? 0));
                $comision = (float) $v->detalles->sum(fn ($d) => (float) $d->subtotal * $pct((float) $d->subtotal) / 100);

                return $porProductos + max((float) $v->prima - $comision, 0);
            }), 2));
    }
}
