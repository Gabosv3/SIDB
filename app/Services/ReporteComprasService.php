<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Proveedor;
use Illuminate\Support\Carbon;

/**
 * Reportes de compras por periodo (semana/quincena/mes) — cuánto se compró
 * a cada proveedor, saldo pendiente de pago, productos más comprados, y
 * tendencia en el tiempo. Mismo patrón que ReporteVentasService/ReporteCobrosService.
 * fecha_compra es datetime (no date), así que los límites de rango usan
 * endOfDay para no excluir compras registradas después de medianoche.
 */
class ReporteComprasService
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
     * Una fila por proveedor con sus métricas del periodo. Las compras
     * canceladas no cuentan.
     *
     * @param  int[]  $proveedorIds  Vacío = todos.
     */
    public static function resumenPorProveedor(Carbon $inicio, Carbon $fin, array $proveedorIds = []): array
    {
        $inicioStr = $inicio->toDateString();
        $finStr = $fin->copy()->endOfDay()->toDateTimeString();

        $proveedores = Proveedor::where('activo', true)
            ->when(! empty($proveedorIds), fn ($q) => $q->whereIn('id', $proveedorIds))
            ->get();

        $filas = $proveedores->map(function (Proveedor $proveedor) use ($inicioStr, $finStr) {
            $comprasQuery = Compra::where('proveedor_id', $proveedor->id)
                ->whereBetween('fecha_compra', [$inicioStr, $finStr])
                ->where('estado', '!=', 'cancelada');

            $totalCompras = (clone $comprasQuery)->count();
            $totalComprado = (float) (clone $comprasQuery)->sum('total');
            $saldoPendiente = (float) (clone $comprasQuery)->sum('saldo_pendiente');
            $ticketPromedio = $totalCompras > 0 ? round($totalComprado / $totalCompras, 2) : 0.0;

            return [
                'proveedor' => $proveedor,
                'total_compras' => $totalCompras,
                'total_comprado' => round($totalComprado, 2),
                'saldo_pendiente' => round($saldoPendiente, 2),
                'ticket_promedio' => $ticketPromedio,
            ];
        });

        return $filas->sortByDesc('total_comprado')->values()->all();
    }

    public static function totales(array $resumen): array
    {
        $col = collect($resumen);

        return [
            'total_comprado' => round($col->sum('total_comprado'), 2),
            'total_compras' => $col->sum('total_compras'),
            'saldo_pendiente' => round($col->sum('saldo_pendiente'), 2),
            'ticket_promedio' => $col->sum('total_compras') > 0
                ? round($col->sum('total_comprado') / $col->sum('total_compras'), 2)
                : 0.0,
        ];
    }

    /**
     * Productos más comprados del periodo (por monto), sumando entre todos
     * los proveedores filtrados.
     *
     * @param  int[]  $proveedorIds
     */
    public static function topProductos(Carbon $inicio, Carbon $fin, array $proveedorIds = [], int $limite = 10): array
    {
        $compraIds = Compra::whereBetween('fecha_compra', [$inicio->toDateString(), $fin->copy()->endOfDay()->toDateTimeString()])
            ->where('estado', '!=', 'cancelada')
            ->when(! empty($proveedorIds), fn ($q) => $q->whereIn('proveedor_id', $proveedorIds))
            ->select('id');

        return DetalleCompra::whereIn('compra_id', $compraIds)
            ->join('productos', 'productos.id', '=', 'detalle_compras.producto_id')
            ->selectRaw('productos.id, productos.nombre, productos.codigo, SUM(detalle_compras.cantidad) as unidades, SUM(detalle_compras.subtotal) as total')
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
     * Total comprado por día dentro del rango, sumando todos los
     * proveedores filtrados (para la gráfica de tendencia).
     *
     * @param  int[]  $proveedorIds
     */
    public static function tendencia(Carbon $inicio, Carbon $fin, array $proveedorIds = []): array
    {
        $porDia = Compra::whereBetween('fecha_compra', [$inicio->toDateString(), $fin->copy()->endOfDay()->toDateTimeString()])
            ->where('estado', '!=', 'cancelada')
            ->when(! empty($proveedorIds), fn ($q) => $q->whereIn('proveedor_id', $proveedorIds))
            ->selectRaw('DATE(fecha_compra) AS dia, SUM(total) AS total')
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
