<?php

namespace App\Services;

use App\Models\Cobrador;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\VisitaCobro;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reportes de cobros por periodo (semana/quincena/mes) — complementa a
 * ResumenCobrosDiaService (que es siempre por UN día) con vistas agregadas
 * por rango de fechas: cuánto cobró cada cobrador, a cuántos clientes con
 * saldo pendiente nunca visitó en todo el periodo, efectividad de cobro,
 * morosidad actual de su cartera, y comparativo/tendencia entre cobradores.
 */
class ReporteCobrosService
{
    /**
     * Rango de fechas [inicio, fin] para un tipo de periodo, anclado a una
     * fecha de referencia (normalmente "hoy").
     */
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
     * Una fila por cobrador con todas las métricas del periodo.
     *
     * @param  int[]  $cobradorIds  Vacío = todos.
     */
    public static function resumenPorCobrador(Carbon $inicio, Carbon $fin, array $cobradorIds = []): array
    {
        $inicioStr = $inicio->toDateString();
        $finStr = $fin->toDateString();

        $cobradores = Cobrador::with('user')
            ->where('activo', true)
            ->where('excluir_reportes', false)
            ->when(! empty($cobradorIds), fn ($q) => $q->whereIn('id', $cobradorIds))
            ->get();

        $filas = $cobradores->map(function (Cobrador $cobrador) use ($inicioStr, $finStr, $inicio, $fin) {
            $userId = $cobrador->user_id;

            $totalCobrado = $userId
                ? (float) PagoVenta::where('user_id', $userId)
                    ->whereBetween('fecha_pago', [$inicioStr, $finStr])
                    ->whereNull('anulado_en')
                    ->sum('monto')
                : 0.0;

            $clientesConPago = $userId
                ? PagoVenta::where('user_id', $userId)
                    ->whereBetween('fecha_pago', [$inicioStr, $finStr])
                    ->whereNull('anulado_en')
                    ->distinct()
                    ->pluck('cliente_id')
                : collect();

            $clientesConVisita = $userId
                ? VisitaCobro::where('user_id', $userId)
                    ->whereBetween('created_at', [$inicio->copy()->startOfDay(), $fin->copy()->endOfDay()])
                    ->distinct()
                    ->pluck('cliente_id')
                : collect();

            $clientesAtendidos = $clientesConPago->merge($clientesConVisita)->unique();

            // Clientes de este cobrador con saldo pendiente — la cartera que
            // debería estar visitando este periodo.
            $clientesConSaldoIds = $cobrador->clientes()
                ->where('activo', true)
                ->whereHas('gestionesCobro', fn ($q) => $q->whereIn('estado', ['pendiente', 'parcialmente_cobrado']))
                ->pluck('clientes.id');

            $noVisitados = $clientesConSaldoIds->diff($clientesAtendidos)->count();

            $totalVisitasSinCobro = $clientesConVisita->diff($clientesConPago)->count();
            $totalAtendidos = $clientesAtendidos->count();
            $efectividad = $totalAtendidos > 0
                ? round(($clientesConPago->count() / $totalAtendidos) * 100, 1)
                : 0.0;

            $rutaIds = $cobrador->rutasCobro()->pluck('id');
            $moraQuery = GestionCobro::whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                ->whereDate('fecha_vencimiento', '<', today())
                ->whereHas('cliente', fn ($q) => $q->whereIn('ruta_cobro_id', $rutaIds));

            return [
                'cobrador' => $cobrador,
                'total_cobrado' => round($totalCobrado, 2),
                'clientes_atendidos' => $totalAtendidos,
                'clientes_con_pago' => $clientesConPago->count(),
                'visitas_sin_cobro' => $totalVisitasSinCobro,
                'clientes_no_visitados' => $noVisitados,
                'clientes_con_saldo' => $clientesConSaldoIds->count(),
                'efectividad_pct' => $efectividad,
                'morosidad_cantidad' => (clone $moraQuery)->count(),
                'morosidad_monto' => round((float) (clone $moraQuery)->selectRaw('SUM(monto_cuota - monto_pagado) AS total')->value('total'), 2),
            ];
        });

        return $filas->sortByDesc('total_cobrado')->values()->all();
    }

    public static function totales(array $resumen): array
    {
        $col = collect($resumen);

        return [
            'total_cobrado' => round($col->sum('total_cobrado'), 2),
            'clientes_no_visitados' => $col->sum('clientes_no_visitados'),
            'clientes_atendidos' => $col->sum('clientes_atendidos'),
            'morosidad_monto' => round($col->sum('morosidad_monto'), 2),
            'morosidad_cantidad' => $col->sum('morosidad_cantidad'),
            'efectividad_promedio' => $col->count() > 0 ? round($col->avg('efectividad_pct'), 1) : 0.0,
        ];
    }

    /**
     * Total cobrado por día dentro del rango (para la gráfica de tendencia),
     * sumando todos los cobradores filtrados.
     *
     * @param  int[]  $cobradorIds
     */
    public static function tendencia(Carbon $inicio, Carbon $fin, array $cobradorIds = []): array
    {
        $userIds = Cobrador::where('activo', true)
            ->where('excluir_reportes', false)
            ->when(! empty($cobradorIds), fn ($q) => $q->whereIn('id', $cobradorIds))
            ->pluck('user_id')
            ->filter();

        $porDia = PagoVenta::whereIn('user_id', $userIds)
            ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
            ->whereNull('anulado_en')
            ->selectRaw('fecha_pago, SUM(monto) AS total')
            ->groupBy('fecha_pago')
            ->pluck('total', 'fecha_pago');

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
