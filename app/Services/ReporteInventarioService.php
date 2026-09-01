<?php

namespace App\Services;

use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Support\Carbon;

/**
 * Foto del estado actual del inventario: productos con stock bajo el mínimo,
 * valorización total del catálogo (a costo y a venta), y productos sin
 * movimiento de stock reciente (posible sobre-stock o catálogo obsoleto).
 */
class ReporteInventarioService
{
    public static function totales(): array
    {
        $productos = Producto::where('activo', true)->get(['stock', 'precio_compra', 'precio_venta', 'stock_minimo']);

        return [
            'total_productos' => $productos->count(),
            'valor_costo' => round((float) $productos->sum(fn (Producto $p) => $p->stock * $p->precio_compra), 2),
            'valor_venta' => round((float) $productos->sum(fn (Producto $p) => $p->stock * $p->precio_venta), 2),
            'unidades_totales' => (int) $productos->sum('stock'),
            'productos_stock_bajo' => $productos->filter(fn (Producto $p) => $p->stock <= $p->stock_minimo)->count(),
        ];
    }

    /**
     * Productos con stock en o por debajo de su stock mínimo configurado.
     */
    public static function stockBajo(): array
    {
        return Producto::where('activo', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->get(['id', 'codigo', 'nombre', 'stock', 'stock_minimo', 'precio_venta'])
            ->map(fn (Producto $p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'stock' => $p->stock,
                'stock_minimo' => $p->stock_minimo,
                'faltante' => max(0, $p->stock_minimo - $p->stock),
            ])
            ->all();
    }

    /**
     * Los productos de mayor valor en inventario (cantidad × costo), para
     * ver dónde está concentrado el capital invertido en stock.
     */
    public static function mayorValorizacion(int $limite = 15): array
    {
        return Producto::where('activo', true)
            ->where('stock', '>', 0)
            ->get(['id', 'codigo', 'nombre', 'stock', 'precio_compra', 'precio_venta'])
            ->map(fn (Producto $p) => [
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'stock' => $p->stock,
                'valor_costo' => round($p->stock * $p->precio_compra, 2),
                'valor_venta' => round($p->stock * $p->precio_venta, 2),
            ])
            ->sortByDesc('valor_costo')
            ->take($limite)
            ->values()
            ->all();
    }

    /**
     * Productos activos con stock que no han tenido ningún movimiento de
     * stock (entrada/salida) en los últimos $dias días — posible catálogo
     * obsoleto o sobre-stock que no está rotando.
     */
    public static function sinMovimiento(int $dias = 60, int $limite = 30): array
    {
        $desde = Carbon::now()->subDays($dias);

        $productoIdsConMovimiento = MovimientoStock::where('created_at', '>=', $desde)
            ->distinct()
            ->pluck('producto_id');

        return Producto::where('activo', true)
            ->where('stock', '>', 0)
            ->whereNotIn('id', $productoIdsConMovimiento)
            ->orderByDesc('stock')
            ->limit($limite)
            ->get(['id', 'codigo', 'nombre', 'stock', 'precio_compra', 'precio_venta'])
            ->map(fn (Producto $p) => [
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'stock' => $p->stock,
                'valor_costo' => round($p->stock * $p->precio_compra, 2),
            ])
            ->all();
    }
}
