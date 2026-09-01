<?php

namespace App\Services;

use App\Models\DetalleVenta;
use App\Models\Vendedor;
use App\Models\Venta;
use Illuminate\Support\Carbon;

/**
 * Reportes de ventas por periodo (semana/quincena/mes) — cuánto vendió cada
 * vendedor, ticket promedio, comparativo entre vendedores, top de productos
 * vendidos, y tendencia en el tiempo. Mismo patrón que ReporteCobrosService.
 */
class ReporteVentasService
{
    public static function rango(string $tipo, string $fechaReferencia): array
    {
        $ref = Carbon::parse($fechaReferencia);

        return match ($tipo) {
            'semana' => [$ref->copy()->startOfWeek(Carbon::MONDAY), $ref->copy()->endOfWeek(Carbon::SUNDAY)],
            'quincena' => $ref->day <= 15
                ? [$ref->copy()->startOfMonth(), $ref->copy()->startOfMonth()->addDays(14)]
                : [$ref->copy()->startOfMonth()->addDays(15), $ref->copy()->endOfMonth()],
            'mes' => [$ref->copy()->startOfMonth(), $ref->copy()->endOfMonth()],
            default => [$ref->copy()->startOfWeek(Carbon::MONDAY), $ref->copy()->endOfWeek(Carbon::SUNDAY)],
        };
    }

    /**
     * Una fila por vendedor con sus métricas del periodo. Las ventas
     * canceladas/devueltas no cuentan — reflejan lo que realmente quedó
     * vendido, no lo que se intentó vender.
     *
     * @param  int[]  $vendedorIds  Vacío = todos.
     */
    public static function resumenPorVendedor(Carbon $inicio, Carbon $fin, array $vendedorIds = []): array
    {
        $inicioStr = $inicio->toDateString();
        $finStr = $fin->copy()->endOfDay()->toDateTimeString();

        $vendedores = Vendedor::where('activo', true)
            ->when(! empty($vendedorIds), fn ($q) => $q->whereIn('id', $vendedorIds))
            ->get();

        $filas = $vendedores->map(function (Vendedor $vendedor) use ($inicioStr, $finStr) {
            $ventasQuery = Venta::where('vendedor_id', $vendedor->id)
                ->whereBetween('fecha_venta', [$inicioStr, $finStr])
                ->whereNotIn('estado', ['cancelada', 'devuelta']);

            $totalVentas = (clone $ventasQuery)->count();
            $totalVendido = (float) (clone $ventasQuery)->sum('total');
            $totalUnidades = (int) DetalleVenta::whereIn('venta_id', (clone $ventasQuery)->select('ventas.id'))->sum('cantidad');
            $ticketPromedio = $totalVentas > 0 ? round($totalVendido / $totalVentas, 2) : 0.0;

            $ventasCredito = (clone $ventasQuery)->where('tipo_pago', 'credito')->count();
            $ventasContado = (clone $ventasQuery)->where('tipo_pago', 'contado')->count();

            return [
                'vendedor' => $vendedor,
                'total_ventas' => $totalVentas,
                'total_vendido' => round($totalVendido, 2),
                'total_unidades' => $totalUnidades,
                'ticket_promedio' => $ticketPromedio,
                'ventas_credito' => $ventasCredito,
                'ventas_contado' => $ventasContado,
            ];
        });

        return $filas->sortByDesc('total_vendido')->values()->all();
    }

    public static function totales(array $resumen): array
    {
        $col = collect($resumen);

        return [
            'total_vendido' => round($col->sum('total_vendido'), 2),
            'total_ventas' => $col->sum('total_ventas'),
            'total_unidades' => $col->sum('total_unidades'),
            'ticket_promedio' => $col->sum('total_ventas') > 0
                ? round($col->sum('total_vendido') / $col->sum('total_ventas'), 2)
                : 0.0,
        ];
    }

    /**
     * Productos más vendidos del periodo (por monto), sumando entre todos
     * los vendedores filtrados.
     *
     * @param  int[]  $vendedorIds
     */
    public static function topProductos(Carbon $inicio, Carbon $fin, array $vendedorIds = [], int $limite = 10): array
    {
        $ventaIds = Venta::whereBetween('fecha_venta', [$inicio->toDateString(), $fin->copy()->endOfDay()->toDateTimeString()])
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->when(! empty($vendedorIds), fn ($q) => $q->whereIn('vendedor_id', $vendedorIds))
            ->select('id');

        return DetalleVenta::whereIn('venta_id', $ventaIds)
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->selectRaw('productos.id, productos.nombre, productos.codigo, SUM(detalle_ventas.cantidad) as unidades, SUM(detalle_ventas.subtotal) as total')
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->orderByDesc('total')
            ->limit($limite)
            ->get()
            ->map(fn ($p) => [
                'nombre' => $p->nombre,
                'codigo' => $p->codigo,
                'unidades' => (int) $p->unidades,
                'total' => round((float) $p->total, 2),
            ])
            ->all();
    }

    /**
     * Total vendido por día dentro del rango, sumando todos los vendedores
     * filtrados (para la gráfica de tendencia).
     *
     * @param  int[]  $vendedorIds
     */
    public static function tendencia(Carbon $inicio, Carbon $fin, array $vendedorIds = []): array
    {
        $porDia = Venta::whereBetween('fecha_venta', [$inicio->toDateString(), $fin->copy()->endOfDay()->toDateTimeString()])
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->when(! empty($vendedorIds), fn ($q) => $q->whereIn('vendedor_id', $vendedorIds))
            ->selectRaw('DATE(fecha_venta) AS dia, SUM(total) AS total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $resultado = [];
        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $resultado[] = [
                'fecha' => $d->format('d/m'),
                'total' => round((float) ($porDia[$d->toDateString()] ?? 0), 2),
            ];
        }

        return $resultado;
    }
}
