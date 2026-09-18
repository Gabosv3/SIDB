<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ResumenCobrosDiaService;
use App\Services\ResumenEncuestasClienteService;
use App\Services\ResumenGarantiasService;
use App\Services\ResumenReintegrosService;
use App\Services\ResumenVentasDiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminResumenesController extends Controller
{
    #[OA\Get(
        path: '/admin/resumenes-dia',
        summary: 'Resúmenes del día (solo super admin)',
        description: 'Los mismos 5 resúmenes del panel administrativo (Ventas, Cobros, Encuestas, Reintegros, Garantías) para una fecha, en un solo llamado. Restringido al rol "super_admin".',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), description: 'Default: hoy'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Totales de los 5 resúmenes del día'),
            new OA\Response(response: 403, description: 'Sin permiso de super admin'),
        ],
    )]
    public function resumenesDia(Request $request): JsonResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            return response()->json(['mensaje' => 'No autorizado.'], 403);
        }

        $fecha = $request->query('fecha') ?: today()->toDateString();

        $ventas = ResumenVentasDiaService::resumen($fecha);
        $totalesVentas = ResumenVentasDiaService::totales($ventas);

        $totalesCobros = ResumenCobrosDiaService::totalesSimples($fecha);

        $encuestas = ResumenEncuestasClienteService::resumen($fecha);
        $totalesEncuestas = ResumenEncuestasClienteService::totales($encuestas);

        $reintegros = ResumenReintegrosService::resumen($fecha);
        $totalesReintegros = ResumenReintegrosService::totales($reintegros);

        $garantias = ResumenGarantiasService::resumen($fecha);
        $totalesGarantias = ResumenGarantiasService::totales($garantias);

        return response()->json([
            'fecha' => $fecha,
            'ventas' => [
                'total_vendido'       => (float) ($totalesVentas['total_vendido'] ?? 0),
                'total_ventas'        => $totalesVentas['total_ventas'] ?? 0,
                'clientes_nuevos'     => $totalesVentas['clientes_nuevos'] ?? 0,
                'clientes_recurrentes'=> $totalesVentas['clientes_recurrentes'] ?? 0,
                'ventas_canceladas'   => $totalesVentas['ventas_canceladas'] ?? 0,
            ],
            'cobros' => [
                'total_cobrado'      => (float) ($totalesCobros['total_cobrado'] ?? 0),
                'total_pagos'        => $totalesCobros['total_pagos'] ?? 0,
                'clientes_visitados' => $totalesCobros['clientes_visitados'] ?? 0,
                'total_sin_cobro'    => $totalesCobros['total_sin_cobro'] ?? 0,
                'total_pendientes'   => $totalesCobros['total_pendientes'] ?? 0,
            ],
            'encuestas' => [
                'total_encuestas'  => $totalesEncuestas['total_encuestas'] ?? 0,
                'coinciden'        => $totalesEncuestas['coinciden'] ?? 0,
                'con_diferencia'   => $totalesEncuestas['con_diferencia'] ?? 0,
                'monto_diferencia' => (float) ($totalesEncuestas['monto_diferencia'] ?? 0),
            ],
            'reintegros' => [
                'total'           => $totalesReintegros['total'] ?? 0,
                'sin_asignar'     => $totalesReintegros['sin_asignar'] ?? 0,
                'recuperados'     => $totalesReintegros['recuperados'] ?? 0,
                'no_recuperados'  => $totalesReintegros['no_recuperados'] ?? 0,
                'monto_adeudado'  => (float) ($totalesReintegros['monto_adeudado'] ?? 0),
            ],
            'garantias' => [
                'total'       => $totalesGarantias['total'] ?? 0,
                'pendientes'  => $totalesGarantias['pendientes'] ?? 0,
                'en_proceso'  => $totalesGarantias['en_proceso'] ?? 0,
                'resueltas'   => $totalesGarantias['resueltas'] ?? 0,
                'rechazadas'  => $totalesGarantias['rechazadas'] ?? 0,
            ],
        ]);
    }
}
