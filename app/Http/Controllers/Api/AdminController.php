<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagoVenta;
use App\Models\Venta;
use App\Services\ResumenCobrosDiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminController extends Controller
{
    #[OA\Get(
        path: '/admin/kpis',
        summary: 'KPIs del negocio (solo super admin)',
        description: 'Panel resumido de ventas, cobros y mora a nivel de toda la distribuidora. Restringido al rol "super_admin".',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        responses: [
            new OA\Response(response: 200, description: 'KPIs del negocio'),
            new OA\Response(response: 403, description: 'Sin permiso de super admin'),
        ],
    )]
    public function kpis(Request $request): JsonResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            return response()->json(['mensaje' => 'No autorizado.'], 403);
        }

        $inicioSemana = now()->startOfWeek();
        $inicioSemanaPasada = (clone $inicioSemana)->subWeek();
        $finSemanaPasada = (clone $inicioSemana)->subSecond();
        $inicioMes = now()->startOfMonth();
        $inicioMesPasado = (clone $inicioMes)->subMonthNoOverflow();
        $finMesPasado = (clone $inicioMes)->subSecond();

        $vendidoEntre = fn ($desde, $hasta) => (float) Venta::whereNotIn('estado', ['cancelada', 'devuelta'])
            ->whereBetween('fecha_venta', [$desde, $hasta])->sum('total');

        $ventasEntre = fn ($desde, $hasta) => Venta::whereNotIn('estado', ['cancelada', 'devuelta'])
            ->whereBetween('fecha_venta', [$desde, $hasta])->count();

        $cobradoEntre = fn ($desde, $hasta) => (float) PagoVenta::whereNull('anulado_en')
            ->whereBetween('fecha_pago', [$desde, $hasta])->sum('monto');

        $morosidad = ResumenCobrosDiaService::morosidad();

        $topVendedores = Venta::whereNotIn('estado', ['cancelada', 'devuelta'])
            ->whereBetween('fecha_venta', [$inicioMes, now()])
            ->selectRaw('vendedor_id, SUM(total) as total')
            ->groupBy('vendedor_id')
            ->orderByDesc('total')
            ->with('vendedor:id,nombre,apellido')
            ->take(5)
            ->get()
            ->filter(fn ($v) => $v->vendedor)
            ->map(fn ($v) => [
                'nombre' => trim($v->vendedor->nombre . ' ' . $v->vendedor->apellido),
                'total'  => (float) $v->total,
            ])
            ->values();

        return response()->json([
            'vendido_semana_actual'  => $vendidoEntre($inicioSemana, now()),
            'vendido_semana_pasada'  => $vendidoEntre($inicioSemanaPasada, $finSemanaPasada),
            'vendido_mes_actual'     => $vendidoEntre($inicioMes, now()),
            'vendido_mes_pasado'     => $vendidoEntre($inicioMesPasado, $finMesPasado),
            'ventas_hoy'             => $ventasEntre(today(), now()),
            'cobrado_semana_actual'  => $cobradoEntre($inicioSemana, now()),
            'cobrado_semana_pasada'  => $cobradoEntre($inicioSemanaPasada, $finSemanaPasada),
            'cobrado_mes_actual'     => $cobradoEntre($inicioMes, now()),
            'cobrado_mes_pasado'     => $cobradoEntre($inicioMesPasado, $finMesPasado),
            'cuentas_en_mora'        => $morosidad['cantidad'] ?? 0,
            'monto_en_mora'          => (float) ($morosidad['monto'] ?? 0),
            'top_vendedores_mes'     => $topVendedores,
        ]);
    }
}
