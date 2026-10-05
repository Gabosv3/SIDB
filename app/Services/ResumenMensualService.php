<?php

namespace App\Services;

use App\Models\AnticipoCobrador;
use App\Models\AnticipoVendedor;
use App\Models\AsignacionDiaria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\ComisionTramo;
use App\Models\DetalleVenta;
use App\Models\Garantia;
use App\Models\PagoCompra;
use App\Models\PagoVenta;
use App\Models\Reintegro;
use App\Models\User;
use App\Models\Vale;
use App\Models\Vendedor;
use App\Models\Venta;
use Illuminate\Support\Carbon;

/**
 * Resumen del mes: ventas, cobros, compras, gastos (con vehículos), comisiones,
 * cartera, operación y un flujo estimado. Todo se calcula con los mismos
 * criterios de las pantallas existentes (ventas no canceladas/devueltas,
 * pagos sin anular, comisión de vendedores por tramo y por producto sobre
 * ventas a crédito/mixtas, comisión de cobradores 8% de lo cobrado).
 */
class ResumenMensualService
{
    public const COMISION_COBRADOR_PCT = 8;

    public static function calcular(Carbon $mes, ?int $sucursalId = null): array
    {
        $inicio = $mes->copy()->startOfMonth()->startOfDay();
        $fin = $mes->copy()->endOfMonth()->endOfDay();

        $ventas = self::ventas($inicio, $fin, $sucursalId);
        $cobros = self::cobros($inicio, $fin, $sucursalId);
        $compras = self::compras($inicio, $fin);
        $gastos = self::gastos($inicio, $fin, $sucursalId);
        $comisiones = self::comisiones($inicio, $fin, $sucursalId, $cobros);
        $cartera = self::cartera($sucursalId);
        $operacion = self::operacion($inicio, $fin, $sucursalId);

        // Flujo ESTIMADO: lo que entró en caja menos lo que salió. No es contabilidad:
        // las comisiones son estimadas y no se incluyen otros costos (sueldos, etc.).
        $entradas = round($cobros['total'] + $ventas['efectivo_al_vender'], 2);
        $salidas = round($gastos['total_aprobado'] + $compras['pagado_proveedores'] + $comisiones['total'], 2);

        return [
            'mes' => $inicio,
            'ventas' => $ventas,
            'cobros' => $cobros,
            'compras' => $compras,
            'gastos' => $gastos,
            'comisiones' => $comisiones,
            'cartera' => $cartera,
            'operacion' => $operacion,
            'flujo' => [
                'entradas' => $entradas,
                'salidas' => $salidas,
                'neto' => round($entradas - $salidas, 2),
            ],
        ];
    }

    private static function ventasBase(Carbon $inicio, Carbon $fin, ?int $sucursalId)
    {
        return Venta::withoutGlobalScopes()
            ->whereBetween('fecha_venta', [$inicio, $fin])
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId));
    }

    private static function ventas(Carbon $inicio, Carbon $fin, ?int $sucursalId): array
    {
        $validas = self::ventasBase($inicio, $fin, $sucursalId)
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->with(['detalles', 'vendedor:id,nombre,apellido'])
            ->get();

        $anuladas = self::ventasBase($inicio, $fin, $sucursalId)->whereIn('estado', ['cancelada', 'devuelta']);

        $porTipo = $validas->groupBy('tipo_pago')->map(fn ($g) => ['cantidad' => $g->count(), 'total' => round((float) $g->sum('total'), 2)]);

        // Efectivo que entró al momento de vender, según el tipo de pago de la VENTA (el
        // tipo de cada línea no es confiable en ventas importadas): contado = todo el total;
        // crédito = solo la prima; mixta = líneas al contado + prima.
        $efectivo = $validas->sum(fn (Venta $v) => match ($v->tipo_pago) {
            'contado' => (float) $v->total,
            'mixta' => (float) $v->detalles->where('tipo_pago', 'contado')->sum('subtotal') + (float) $v->prima,
            default => (float) $v->prima,
        });

        $clientesNuevos = Cliente::withoutGlobalScopes()
            ->whereIn('id', $validas->pluck('cliente_id')->filter()->unique())
            ->whereDoesntHave('ventas', fn ($q) => $q->where('fecha_venta', '<', $inicio)->whereNotIn('estado', ['cancelada', 'devuelta']))
            ->count();

        $porVendedor = $validas->groupBy('vendedor_id')->map(function ($g) {
            $v = $g->first()->vendedor;

            return [
                'vendedor' => $v ? trim($v->nombre . ' ' . $v->apellido) : 'Sin vendedor',
                'ventas' => $g->count(),
                'total' => round((float) $g->sum('total'), 2),
            ];
        })->sortByDesc('total')->values();

        return [
            'cantidad' => $validas->count(),
            'total' => round((float) $validas->sum('total'), 2),
            'descuentos' => round((float) $validas->sum('descuento_monto'), 2),
            'primas' => round((float) $validas->sum('prima'), 2),
            'ticket_promedio' => $validas->count() ? round((float) $validas->sum('total') / $validas->count(), 2) : 0.0,
            'por_tipo' => $porTipo,
            'efectivo_al_vender' => round((float) $efectivo, 2),
            'clientes_nuevos' => $clientesNuevos,
            'anuladas_cantidad' => (clone $anuladas)->count(),
            'anuladas_total' => round((float) (clone $anuladas)->sum('total'), 2),
            'por_vendedor' => $porVendedor,
        ];
    }

    private static function cobros(Carbon $inicio, Carbon $fin, ?int $sucursalId): array
    {
        $pagos = PagoVenta::query()
            ->whereNull('anulado_en')
            ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
            ->when($sucursalId, fn ($q) => $q->whereHas('venta', fn ($v) => $v->withoutGlobalScopes()->where('sucursal_id', $sucursalId)))
            ->get(['id', 'user_id', 'cliente_id', 'monto']);

        $nombres = User::whereIn('id', $pagos->pluck('user_id')->filter()->unique())->pluck('name', 'id');

        $porCobrador = $pagos->groupBy('user_id')->map(fn ($g, $uid) => [
            'cobrador' => $nombres[$uid] ?? 'Sin usuario',
            'pagos' => $g->count(),
            'total' => round((float) $g->sum('monto'), 2),
        ])->sortByDesc('total')->values();

        return [
            'total' => round((float) $pagos->sum('monto'), 2),
            'pagos' => $pagos->count(),
            'clientes' => $pagos->pluck('cliente_id')->unique()->count(),
            'por_cobrador' => $porCobrador,
        ];
    }

    private static function compras(Carbon $inicio, Carbon $fin): array
    {
        $compras = Compra::query()
            ->whereBetween('fecha_compra', [$inicio, $fin])
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->with('proveedor:id,nombre')
            ->get();

        $porProveedor = $compras->groupBy('proveedor_id')->map(fn ($g) => [
            'proveedor' => $g->first()->proveedor?->nombre ?? 'Sin proveedor',
            'compras' => $g->count(),
            'total' => round((float) $g->sum('total'), 2),
        ])->sortByDesc('total')->values();

        return [
            'cantidad' => $compras->count(),
            'total' => round((float) $compras->sum('total'), 2),
            'pagado_proveedores' => round((float) PagoCompra::whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])->sum('monto'), 2),
            'deuda_proveedores' => round((float) Compra::whereNotIn('estado', ['cancelada', 'devuelta'])->sum('saldo_pendiente'), 2),
            'por_proveedor' => $porProveedor,
        ];
    }

    private static function gastos(Carbon $inicio, Carbon $fin, ?int $sucursalId): array
    {
        $base = Vale::withoutGlobalScopes()
            ->whereBetween('fecha_gasto', [$inicio->toDateString(), $fin->toDateString()])
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId));

        $aprobados = (clone $base)->where('estado', 'aprobado')->with('vehiculo:id,placa,marca,modelo')->get();
        $pendientes = (clone $base)->where('estado', 'pendiente');

        $vehiculo = $aprobados->where('tipo', 'vehiculo');

        $porCategoria = $vehiculo->groupBy(fn ($v) => $v->categoria_vehiculo ?: 'sin categoría')
            ->map(fn ($g, $cat) => ['categoria' => ucfirst($cat), 'registros' => $g->count(), 'total' => round((float) $g->sum('monto'), 2)])
            ->sortByDesc('total')->values();

        $porVehiculo = $vehiculo->groupBy('vehiculo_id')->map(function ($g) {
            $veh = $g->first()->vehiculo;

            return [
                'vehiculo' => $veh ? trim($veh->placa . ' ' . $veh->marca . ' ' . $veh->modelo) : 'Sin vehículo',
                'registros' => $g->count(),
                'total' => round((float) $g->sum('monto'), 2),
            ];
        })->sortByDesc('total')->values();

        return [
            'total_aprobado' => round((float) $aprobados->sum('monto'), 2),
            'consumo' => round((float) $aprobados->where('tipo', 'consumo')->sum('monto'), 2),
            'vehiculos' => round((float) $vehiculo->sum('monto'), 2),
            'por_categoria' => $porCategoria,
            'por_vehiculo' => $porVehiculo,
            'pendientes_cantidad' => (clone $pendientes)->count(),
            'pendientes_total' => round((float) (clone $pendientes)->sum('monto'), 2),
        ];
    }

    private static function comisiones(Carbon $inicio, Carbon $fin, ?int $sucursalId, array $cobros): array
    {
        // Tramos en memoria (evita una consulta por cada línea de venta).
        $tramos = ComisionTramo::orderByDesc('desde')->get();
        $pct = function (float $subtotal) use ($tramos): float {
            $t = $tramos->first(fn ($x) => (float) $x->desde <= $subtotal && ($x->hasta === null || (float) $x->hasta >= $subtotal));

            return $t ? (float) $t->porcentaje : 0.0;
        };

        $ventas = self::ventasBase($inicio, $fin, $sucursalId)
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->where('tipo_pago', '!=', 'contado')
            ->whereNotNull('vendedor_id')
            ->with('detalles')
            ->get();

        $vendedores = Vendedor::whereIn('id', $ventas->pluck('vendedor_id')->unique())->get()->keyBy('id');

        $porVendedor = $ventas->groupBy('vendedor_id')->map(function ($g, $id) use ($pct, $vendedores) {
            $v = $vendedores[$id] ?? null;

            return [
                'vendedor' => $v ? trim($v->nombre . ' ' . $v->apellido) : 'Vendedor #' . $id,
                'vendido' => round((float) $g->sum('total'), 2),
                'comision' => round((float) $g->flatMap->detalles->sum(fn (DetalleVenta $d) => (float) $d->subtotal * $pct((float) $d->subtotal) / 100), 2),
            ];
        })->sortByDesc('comision')->values();

        $porCobrador = collect($cobros['por_cobrador'])->map(fn ($c) => [
            'cobrador' => $c['cobrador'],
            'cobrado' => $c['total'],
            'comision' => round($c['total'] * self::COMISION_COBRADOR_PCT / 100, 2),
        ])->values();

        $anticiposVendedores = (float) AnticipoVendedor::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])->sum('monto');
        $anticiposCobradores = (float) AnticipoCobrador::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])->sum('monto');

        $totalVend = round((float) $porVendedor->sum('comision'), 2);
        $totalCob = round((float) $porCobrador->sum('comision'), 2);

        return [
            'vendedores' => $porVendedor,
            'cobradores' => $porCobrador,
            'total_vendedores' => $totalVend,
            'total_cobradores' => $totalCob,
            'total' => round($totalVend + $totalCob, 2),
            'anticipos' => round($anticiposVendedores + $anticiposCobradores, 2),
        ];
    }

    private static function cartera(?int $sucursalId): array
    {
        $clientes = Cliente::withoutGlobalScopes()
            ->where('activo', true)->where('saldo', '>', 0)
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId));

        return [
            'saldo_total' => round((float) (clone $clientes)->sum('saldo'), 2),
            'clientes_con_saldo' => (clone $clientes)->count(),
        ];
    }

    private static function operacion(Carbon $inicio, Carbon $fin, ?int $sucursalId): array
    {
        $rango = [$inicio->toDateString(), $fin->toDateString()];
        $suc = fn ($q) => $q->when($sucursalId, fn ($x) => $x->where('sucursal_id', $sucursalId));

        return [
            'asignaciones' => $suc(AsignacionDiaria::withoutGlobalScopes())->whereBetween('fecha', $rango)->count(),
            'reintegros_asignados' => $suc(Reintegro::withoutGlobalScopes())->whereBetween('fecha_asignacion', $rango)->count(),
            'reintegros_recuperados' => $suc(Reintegro::withoutGlobalScopes())->whereBetween('fecha_asignacion', $rango)->where('estado', 'recuperado')->count(),
            'garantias_reportadas' => $suc(Garantia::withoutGlobalScopes())->whereBetween('fecha_reporte', $rango)->count(),
            'garantias_resueltas' => $suc(Garantia::withoutGlobalScopes())->whereBetween('fecha_reporte', $rango)->where('estado', 'resuelta')->count(),
        ];
    }
}
