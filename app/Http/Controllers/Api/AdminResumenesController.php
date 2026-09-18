<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cobrador;
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
    /** "3,7,12" → [3,7,12]. Vacío si no viene el parámetro. */
    private function idsDeQuery(Request $request, string $param): array
    {
        $raw = $request->query($param);

        if (! $raw) {
            return [];
        }

        return collect(explode(',', (string) $raw))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->values()
            ->all();
    }

    #[OA\Get(
        path: '/admin/cobradores',
        summary: 'Cobradores activos, para filtrar los resúmenes (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
    )]
    public function cobradores(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $cobradores = Cobrador::where('activo', true)
            ->where('excluir_reportes', false)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'apellido', 'sucursal_id']);

        return response()->json($cobradores->map(fn ($c) => [
            'id'          => $c->id,
            'nombre'      => trim($c->nombre . ' ' . $c->apellido),
            'sucursal_id' => $c->sucursal_id,
        ]));
    }

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
        if ($resp = $this->autorizar($request)) {
            return $resp;
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

    #[OA\Get(
        path: '/admin/resumenes-dia/ventas',
        summary: 'Detalle de ventas del día, igual al panel administrativo (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'vendedor_id', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'IDs de vendedor separados por coma'),
        ],
    )]
    public function ventasDetalle(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $fecha = $request->query('fecha') ?: today()->toDateString();
        $resumen = ResumenVentasDiaService::resumen($fecha, $this->idsDeQuery($request, 'vendedor_id'));

        return response()->json([
            'fecha' => $fecha,
            'totales' => ResumenVentasDiaService::totales($resumen),
            'items' => $resumen->map(fn ($r) => [
                'id'              => $r->venta->id,
                'cliente'         => $r->venta->cliente ? $r->venta->cliente->nombre_completo : 'Consumidor Final',
                'vendedor'        => $r->venta->vendedor ? trim($r->venta->vendedor->nombre . ' ' . $r->venta->vendedor->apellido) : ($r->venta->user?->name ?? '—'),
                'total'           => (float) $r->venta->total,
                'tipo_pago'       => $r->venta->tipo_pago,
                'estado'          => $r->venta->estado,
                'es_cliente_nuevo'=> $r->es_cliente_nuevo,
                'hora'            => optional($r->venta->fecha_venta)->format('H:i'),
                'productos'       => $r->venta->detalles->map(fn ($d) => $d->producto?->nombre)->filter()->values(),
            ])->values(),
        ]);
    }

    #[OA\Get(
        path: '/admin/resumenes-dia/cobros',
        summary: 'Detalle de cobros del día por cobrador/ruta, igual al panel administrativo (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'cobrador_id', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'IDs de cobrador separados por coma'),
        ],
    )]
    public function cobrosDetalle(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $fecha = $request->query('fecha') ?: today()->toDateString();
        $resumen = ResumenCobrosDiaService::resumen($fecha, $this->idsDeQuery($request, 'cobrador_id'));

        return response()->json([
            'fecha' => $fecha,
            'totales' => ResumenCobrosDiaService::totales($resumen),
            'grupos' => collect($resumen)->map(fn ($r) => [
                'cobrador'            => trim(($r['cobrador']->nombre ?? '') . ' ' . ($r['cobrador']->apellido ?? '')) ?: ($r['cobrador']->user?->name ?? '—'),
                'ruta'                => $r['ruta']?->nombre,
                'total_cobrado'       => (float) $r['total_cobrado'],
                'total_pagos'         => $r['total_pagos'],
                'clientes_visitados'  => $r['clientes_visitados'],
                'clientes_ruta_inicio'=> $r['clientes_ruta_inicio'],
                'ventas_canceladas'   => $r['ventas_canceladas'],
                'reintegros_enviados' => $r['reintegros_enviados'],
                'por_metodo'          => collect($r['por_metodo'])->values(),
                // Un mismo cobro puede quedar partido en varios PagoVenta
                // (ej. abono a más de una cuota en la misma visita), pero
                // todos comparten el mismo numero_recibo — se agrupan por
                // ahí para que salga como un solo ticket (el mismo recibo
                // que se le entrega al cliente), en vez de una línea por cuota.
                'pagos'               => collect($r['detalle'])
                    ->groupBy(fn ($p) => $p->numero_recibo ?? "sin-recibo-{$p->id}")
                    ->map(function ($pagosDelTicket) {
                        $primero = $pagosDelTicket->first();
                        $validos = $pagosDelTicket->whereNull('anulado_en');

                        return [
                            'cliente'       => $primero->cliente ? trim($primero->cliente->nombre . ' ' . $primero->cliente->apellido) : '—',
                            'codigo'        => $primero->cliente?->codigo_anterior,
                            'numero_recibo' => $primero->numero_recibo,
                            'monto'         => (float) $validos->sum('monto'),
                            'cuotas'        => $pagosDelTicket->count(),
                            'metodos'       => $pagosDelTicket->pluck('metodo_pago')->unique()->values(),
                            'anulado'       => $validos->isEmpty() && $pagosDelTicket->isNotEmpty(),
                        ];
                    })
                    ->values(),
                'visitas_sin_cobro'   => collect($r['visitas_sin_cobro'])->map(fn ($v) => [
                    'cliente' => $v->cliente ? trim($v->cliente->nombre . ' ' . $v->cliente->apellido) : '—',
                ])->values(),
                'pendientes_visitar'  => collect($r['no_visitados'])->map(fn ($c) => [
                    'id'       => $c->id,
                    'nombre'   => trim($c->nombre . ' ' . $c->apellido),
                    'telefono' => $c->telefono_normal,
                    'codigo'   => $c->codigo_anterior,
                ])->values(),
            ])->values(),
        ]);
    }

    #[OA\Get(
        path: '/admin/resumenes-dia/encuestas',
        summary: 'Detalle de encuestas de cliente del día (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'cobrador_id', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'IDs de cobrador separados por coma'),
        ],
    )]
    public function encuestasDetalle(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $fecha = $request->query('fecha') ?: today()->toDateString();
        $resumen = ResumenEncuestasClienteService::resumen($fecha, $this->idsDeQuery($request, 'cobrador_id'));

        return response()->json([
            'fecha' => $fecha,
            'totales' => ResumenEncuestasClienteService::totales($resumen),
            'items' => $resumen->map(fn ($e) => [
                'cliente'    => $e->cliente ? trim($e->cliente->nombre . ' ' . $e->cliente->apellido) : '—',
                'cobrador'   => $e->cobrador ? trim($e->cobrador->nombre . ' ' . $e->cobrador->apellido) : '—',
                'supervisor' => $e->supervisor ? trim($e->supervisor->nombre . ' ' . $e->supervisor->apellido) : null,
                'resultado'  => $e->resultado,
                'diferencia' => (float) $e->diferencia,
                'hora'       => optional($e->created_at)->format('H:i'),
            ])->values(),
        ]);
    }

    #[OA\Get(
        path: '/admin/resumenes-dia/reintegros',
        summary: 'Detalle de reintegros del día (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'vendedor_id', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'IDs de vendedor separados por coma'),
            new OA\Parameter(name: 'cobrador_id', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'IDs de cobrador separados por coma'),
        ],
    )]
    public function reintegrosDetalle(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $fecha = $request->query('fecha') ?: today()->toDateString();

        // El servicio filtra reintegros por vendedor.user_id / cobrador.user_id
        // (quien lo asignó), no por el id del cobrador en sí — misma
        // traducción que hace el panel web antes de llamar al servicio.
        $cobradorIds = $this->idsDeQuery($request, 'cobrador_id');
        $cobradorUserIds = ! empty($cobradorIds)
            ? Cobrador::whereIn('id', $cobradorIds)->pluck('user_id')->filter()->values()->all()
            : [];

        $resumen = ResumenReintegrosService::resumen(
            $fecha,
            $this->idsDeQuery($request, 'vendedor_id'),
            $cobradorUserIds
        );

        return response()->json([
            'fecha' => $fecha,
            'totales' => ResumenReintegrosService::totales($resumen),
            'items' => $resumen->map(fn ($r) => [
                'cliente'        => $r->cliente ? trim($r->cliente->nombre . ' ' . $r->cliente->apellido) : '—',
                'vendedor'       => $r->vendedor ? trim($r->vendedor->nombre . ' ' . $r->vendedor->apellido) : 'Sin asignar',
                'ruta_origen'    => $r->rutaCobroOriginal?->nombre,
                'venta'          => $r->venta?->numero_venta,
                'estado'         => $r->estado,
                'monto_adeudado' => (float) $r->monto_adeudado,
            ])->values(),
        ]);
    }

    #[OA\Get(
        path: '/admin/resumenes-dia/garantias',
        summary: 'Detalle de garantías del día (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        parameters: [new OA\Parameter(name: 'fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))],
    )]
    public function garantiasDetalle(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $fecha = $request->query('fecha') ?: today()->toDateString();
        $resumen = ResumenGarantiasService::resumen($fecha);

        return response()->json([
            'fecha' => $fecha,
            'totales' => ResumenGarantiasService::totales($resumen),
            'items' => $resumen->map(fn ($g) => [
                'cliente'      => $g->cliente ? trim($g->cliente->nombre . ' ' . $g->cliente->apellido) : '—',
                'venta'        => $g->venta?->numero_venta,
                'asignado_a'   => $g->asignadoA?->name,
                'reportado_por'=> $g->reportadoPor?->name,
                'estado'       => $g->estado,
            ])->values(),
        ]);
    }

    private function autorizar(Request $request): ?JsonResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            return response()->json(['mensaje' => 'No autorizado.'], 403);
        }

        return null;
    }
}
