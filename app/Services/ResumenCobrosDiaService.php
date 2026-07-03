<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\RutaCobro;
use App\Models\VisitaCobro;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResumenCobrosDiaService
{
    public static function resumen(string $fecha, ?int $cobradorId = null): array
    {
        $fechaCarbon = Carbon::parse($fecha);

        $cobradores = Cobrador::with('user')
            ->where('activo', true)
            ->where('excluir_reportes', false)
            ->when($cobradorId, fn ($q) => $q->where('id', $cobradorId))
            ->get();

        $resumen = $cobradores->map(function ($cobrador) use ($fechaCarbon) {
            $pagos = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fechaCarbon)
                ->selectRaw('COUNT(DISTINCT cliente_id) AS total_pagos, COUNT(DISTINCT cliente_id) AS clientes_visitados, SUM(monto) AS total_cobrado')
                ->first();

            $porMetodo = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fechaCarbon)
                ->selectRaw('metodo_pago, COUNT(*) AS cantidad, SUM(monto) AS monto')
                ->groupBy('metodo_pago')
                ->get()
                ->keyBy('metodo_pago');

            $detalle = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fechaCarbon)
                ->with('cliente:id,nombre,apellido,codigo_anterior', 'venta:id,numero_venta')
                ->orderBy('created_at')
                ->get();

            // Visitas sin cobro del día
            $visitas = VisitaCobro::where('user_id', $cobrador->user_id)
                ->whereDate('created_at', $fechaCarbon)
                ->with('cliente:id,nombre,apellido,codigo_anterior')
                ->orderBy('created_at')
                ->get();

            // Clientes NO visitados: en rutas del cobrador con cuotas pendientes,
            // sin pago ni visita registrada en la fecha seleccionada
            $diasEs = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
            $diaFecha = $diasEs[$fechaCarbon->dayOfWeekIso - 1];

            $rutasIds = $cobrador->rutasCobro()
                ->where('dia_semana', $diaFecha)
                ->pluck('id');

            $clientesConPago = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fechaCarbon)->pluck('cliente_id');
            $clientesConVisita = VisitaCobro::where('user_id', $cobrador->user_id)
                ->whereDate('created_at', $fechaCarbon)->pluck('cliente_id');
            $clientesAtendidos = $clientesConPago->merge($clientesConVisita)->unique();

            $noVisitados = Cliente::whereIn('ruta_cobro_id', $rutasIds)
                ->whereNotIn('id', $clientesAtendidos)
                ->whereHas('gestionesCobro', fn ($q) => $q->whereIn('estado', ['pendiente', 'parcialmente_cobrado']))
                ->select('id', 'nombre', 'apellido', 'telefono_normal', 'codigo_anterior')
                ->orderBy('nombre')
                ->get();

            return [
                'cobrador' => $cobrador,
                'total_cobrado' => (float) ($pagos->total_cobrado ?? 0),
                'total_pagos' => (int) ($pagos->total_pagos ?? 0),
                'clientes_visitados' => (int) ($pagos->clientes_visitados ?? 0),
                'por_metodo' => $porMetodo,
                'detalle' => $detalle,
                'visitas_sin_cobro' => $visitas,
                'no_visitados' => $noVisitados,
            ];
        })->filter(fn ($r) => $r['total_pagos'] > 0 || $r['visitas_sin_cobro']->isNotEmpty() || $r['no_visitados']->isNotEmpty() || $cobradorId);

        return $resumen->values()->all();
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
