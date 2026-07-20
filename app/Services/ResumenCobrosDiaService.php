<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\RutaCobro;
use App\Models\Vale;
use App\Models\VisitaCobro;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResumenCobrosDiaService
{
    /**
     * Un cobrador puede cubrir más de una ruta el mismo día (día compartido entre
     * 2 rutas, o cobro extra fuera de su ruta habitual). Para que cada ruta se vea
     * como una entrada aparte en vez de sumarse en un solo total del cobrador, se
     * genera una entrada por cada combinación (cobrador, ruta) que tuvo actividad
     * o tenía ruta programada ese día.
     */
    public static function resumen(string $fecha, ?int $cobradorId = null): array
    {
        $fechaCarbon = Carbon::parse($fecha);
        $diasEs = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $diaFecha = $diasEs[$fechaCarbon->dayOfWeekIso - 1];

        $cobradores = Cobrador::with('user')
            ->where('activo', true)
            ->where('excluir_reportes', false)
            ->when($cobradorId, fn ($q) => $q->where('id', $cobradorId))
            ->get();

        $resumen = collect();

        foreach ($cobradores as $cobrador) {
            $pagosTodos = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fechaCarbon)
                ->with('cliente:id,nombre,apellido,codigo_anterior,ruta_cobro_id', 'venta:id,numero_venta,saldo_pendiente')
                ->orderBy('created_at')
                ->get();

            $visitasTodas = VisitaCobro::where('user_id', $cobrador->user_id)
                ->whereDate('created_at', $fechaCarbon)
                ->with('cliente:id,nombre,apellido,codigo_anterior,ruta_cobro_id')
                ->orderBy('created_at')
                ->get();

            $rutasHoyIds = $cobrador->rutasCobro()
                ->where('dia_semana', $diaFecha)
                ->pluck('id');

            $rutaIdsConActividad = $pagosTodos->pluck('cliente.ruta_cobro_id')
                ->merge($visitasTodas->pluck('cliente.ruta_cobro_id'))
                ->merge($rutasHoyIds)
                ->filter()
                ->unique()
                ->values();

            if ($rutaIdsConActividad->isEmpty()) {
                continue;
            }

            $rutas = RutaCobro::whereIn('id', $rutaIdsConActividad)->get()->keyBy('id');

            $clientesAtendidosTodos = $pagosTodos->pluck('cliente_id')
                ->merge($visitasTodas->pluck('cliente_id'))
                ->unique();

            foreach ($rutaIdsConActividad as $rutaId) {
                $pagos = $pagosTodos->filter(fn ($p) => $p->cliente?->ruta_cobro_id === $rutaId)->values();
                $visitas = $visitasTodas->filter(fn ($v) => $v->cliente?->ruta_cobro_id === $rutaId)->values();

                $porMetodo = $pagos->groupBy('metodo_pago')->map(fn ($grupo, $metodo) => (object) [
                    'metodo_pago' => $metodo,
                    'cantidad' => $grupo->count(),
                    'monto' => (float) $grupo->sum('monto'),
                ]);

                // Clientes NO visitados: en ESTA ruta, con cuotas pendientes, sin
                // pago ni visita registrada por este cobrador en la fecha seleccionada.
                $noVisitados = Cliente::where('ruta_cobro_id', $rutaId)
                    ->whereNotIn('id', $clientesAtendidosTodos)
                    ->whereHas('gestionesCobro', fn ($q) => $q->whereIn('estado', ['pendiente', 'parcialmente_cobrado']))
                    ->select('id', 'nombre', 'apellido', 'telefono_normal', 'codigo_anterior')
                    ->orderBy('nombre')
                    ->get();

                $resumen->push([
                    'cobrador' => $cobrador,
                    'ruta' => $rutas->get($rutaId),
                    'total_cobrado' => (float) $pagos->sum('monto'),
                    'total_pagos' => $pagos->pluck('cliente_id')->unique()->count(),
                    'clientes_visitados' => $pagos->pluck('cliente_id')->unique()->count(),
                    'por_metodo' => $porMetodo,
                    'detalle' => $pagos,
                    'visitas_sin_cobro' => $visitas,
                    'no_visitados' => $noVisitados,
                ]);
            }
        }

        return $resumen
            ->filter(fn ($r) => $r['total_pagos'] > 0 || $r['visitas_sin_cobro']->isNotEmpty() || $r['no_visitados']->isNotEmpty() || $cobradorId)
            ->values()
            ->all();
    }

    public static function totales(array $resumen): array
    {
        $col = collect($resumen);

        return [
            'total_cobrado' => round($col->sum('total_cobrado'), 2),
            'total_pagos' => $col->sum('total_pagos'),
            'clientes_visitados' => $col->sum('clientes_visitados'),
            'total_sin_cobro' => $col->sum(fn ($r) => $r['visitas_sin_cobro']->count()),
            'total_no_visitados' => $col->sum(fn ($r) => $r['no_visitados']->count()),
        ];
    }

    /**
     * Totales rápidos por cobrador (user_id) para una fecha, sin detalle ni relaciones.
     * Usado para mostrar cifras precisas en la tabla de POS sin el costo de la consulta completa.
     */
    public static function ligero(string $fecha): Collection
    {
        return PagoVenta::whereDate('fecha_pago', Carbon::parse($fecha))
            ->selectRaw('user_id, COUNT(*) AS pagos, COUNT(DISTINCT cliente_id) AS clientes, SUM(monto) AS cobrado')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Totales globales del negocio (todos los cobradores) para una fecha,
     * sin desglose por cobrador. Usado para comparar "hoy vs ayer".
     */
    public static function totalesSimples(string $fecha): array
    {
        $fechaCarbon = Carbon::parse($fecha);

        $pagos = PagoVenta::whereDate('fecha_pago', $fechaCarbon)
            ->selectRaw('SUM(monto) AS total_cobrado, COUNT(DISTINCT CONCAT(cliente_id, "-", venta_id)) AS total_pagos, COUNT(DISTINCT cliente_id) AS clientes_visitados')
            ->first();

        $totalSinCobro = VisitaCobro::whereDate('created_at', $fechaCarbon)->count();
        $totalPendientes = self::contarPendientes($fechaCarbon);

        return [
            'total_cobrado' => (float) ($pagos->total_cobrado ?? 0),
            'total_pagos' => (int) ($pagos->total_pagos ?? 0),
            'clientes_visitados' => (int) ($pagos->clientes_visitados ?? 0),
            'total_sin_cobro' => $totalSinCobro,
            'total_pendientes' => $totalPendientes,
        ];
    }

    /**
     * Métricas generales del día: cobradores activos, meta diaria (monto esperado),
     * efectividad de cobro y promedio por pago.
     */
    public static function resumenGeneral(string $fecha): array
    {
        $fechaCarbon = Carbon::parse($fecha);

        $totalCobradores = Cobrador::where('activo', true)->where('excluir_reportes', false)->count();

        $userIdsConPago = PagoVenta::whereDate('fecha_pago', $fechaCarbon)->distinct()->pluck('user_id');
        $userIdsConVisita = VisitaCobro::whereDate('created_at', $fechaCarbon)->distinct()->pluck('user_id');
        $cobradoresActivos = $userIdsConPago->merge($userIdsConVisita)->unique()->count();

        $totalEsperado = (float) GestionCobro::whereDate('fecha_vencimiento', $fechaCarbon)
            ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
            ->sum('monto_cuota');

        $pagos = PagoVenta::whereDate('fecha_pago', $fechaCarbon)
            ->selectRaw('SUM(monto) AS total_cobrado, COUNT(DISTINCT CONCAT(cliente_id, "-", venta_id)) AS total_pagos, COUNT(DISTINCT cliente_id) AS clientes_con_pago')
            ->first();

        $visitasSinCobro = VisitaCobro::whereDate('created_at', $fechaCarbon)->count();
        $clientesConPago = (int) ($pagos->clientes_con_pago ?? 0);
        $totalVisitas = $clientesConPago + $visitasSinCobro;

        return [
            'total_cobradores' => $totalCobradores,
            'cobradores_activos' => $cobradoresActivos,
            'total_esperado' => $totalEsperado,
            'total_cobrado' => (float) ($pagos->total_cobrado ?? 0),
            'efectividad_cobro' => $totalVisitas > 0 ? round(($clientesConPago / $totalVisitas) * 100) : 0,
            'promedio_pago' => ($pagos->total_pagos ?? 0) > 0 ? round(((float) ($pagos->total_cobrado ?? 0)) / $pagos->total_pagos, 2) : 0,
        ];
    }

    /**
     * Vales (gastos) registrados por cada cobrador en la fecha indicada, para
     * poder cuadrar cuánto efectivo debe entregar (lo cobrado menos lo que ya
     * gastó de esa plata). Incluye vales en cualquier estado — el efectivo
     * salió de su mano ese día sin importar si administración lo aprueba
     * después; la aprobación solo afecta si se descuenta de su comisión
     * semanal (ver LiquidacionSemanal, que sí filtra por aprobado).
     *
     * Solo cuenta los vales con descuenta_cobro_diario=true: los gastos chicos
     * que el empleado ya pagó de lo cobrado ese día (imprevisto de calle,
     * gasolina, consumo — siempre true cuando vienen del móvil). Los gastos
     * grandes que el administrador registra directo en el sistema (reparación
     * mayor, pagada aparte por la empresa) quedan fuera de este cálculo.
     */
    public static function valesPorCobrador(string $fecha): Collection
    {
        $vales = Vale::whereDate('fecha_gasto', Carbon::parse($fecha))
            ->where('descuenta_cobro_diario', true)
            ->selectRaw("user_id,
                SUM(CASE WHEN tipo = 'consumo' THEN monto ELSE 0 END) AS total_consumo,
                SUM(CASE WHEN tipo = 'vehiculo' THEN monto ELSE 0 END) AS total_vehiculo,
                SUM(monto) AS total_gastado")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        if ($vales->isEmpty()) {
            return collect();
        }

        return Cobrador::whereIn('user_id', $vales->keys())
            ->where('activo', true)
            ->where('excluir_reportes', false)
            ->get()
            ->map(fn (Cobrador $c) => [
                'cobrador'       => $c,
                'total_consumo'  => round((float) $vales[$c->user_id]->total_consumo, 2),
                'total_vehiculo' => round((float) $vales[$c->user_id]->total_vehiculo, 2),
                'total_gastado'  => round((float) $vales[$c->user_id]->total_gastado, 2),
            ])
            ->values();
    }

    private static function contarPendientes(Carbon $fechaCarbon): int
    {
        $diasEs = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $diaFecha = $diasEs[$fechaCarbon->dayOfWeekIso - 1];

        $rutasIds = RutaCobro::where('dia_semana', $diaFecha)->pluck('id');

        $clientesConPago = PagoVenta::whereDate('fecha_pago', $fechaCarbon)->pluck('cliente_id');
        $clientesConVisita = VisitaCobro::whereDate('created_at', $fechaCarbon)->pluck('cliente_id');
        $clientesAtendidos = $clientesConPago->merge($clientesConVisita)->unique();

        return Cliente::whereIn('ruta_cobro_id', $rutasIds)
            ->whereNotIn('id', $clientesAtendidos)
            ->whereHas('gestionesCobro', fn ($q) => $q->whereIn('estado', ['pendiente', 'parcialmente_cobrado']))
            ->count();
    }
}
