<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsignacionDiaria;
use App\Models\DetalleVenta;
use App\Models\GestionCobro;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class VentaController extends Controller
{
    #[OA\Get(
        path: '/ventas',
        summary: 'Listar ventas del usuario autenticado',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        parameters: [
            new OA\Parameter(name: 'sucursal_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['completada', 'pendiente', 'anulada'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de ventas',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/Paginacion'),
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Venta')),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Venta::with('cliente:id,nombre,apellido')
            ->where('user_id', $request->user()->id);

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->integer('sucursal_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json(
            $query->latest('fecha_venta')
                ->paginate($request->integer('per_page', 20))
        );
    }

    #[OA\Get(
        path: '/ventas/{id}',
        summary: 'Obtener una venta con detalles y pagos',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Venta con relaciones', content: new OA\JsonContent(ref: '#/components/schemas/Venta')),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrada'),
        ],
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $venta = Venta::with([
            'cliente:id,nombre,apellido,dui,telefono_normal',
            'detalles.producto:id,nombre,codigo,unidad_medida',
            'pagos:id,venta_id,monto,fecha_pago,metodo_pago',
        ])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($venta);
    }

    /**
     * Crear una venta con sus detalles.
     *
     * Body JSON:
     * {
     *   "cliente_id"           : 1,           // requerido
     *   "sucursal_id"          : 1,           // requerido
     *   "tipo_pago"            : "contado",   // contado|credito
     *   "dias_credito"         : 30,          // solo si tipo_pago=credito
     *   "descuento_porcentaje" : 0,
     *   "observaciones"        : "",
     *   "detalles": [
     *     {
     *       "producto_id"         : 5,
     *       "cantidad"            : 2,
     *       "precio_unitario"     : 15.00,
     *       "descuento_porcentaje": 0
     *     }
     *   ]
     * }
     */
    #[OA\Post(
        path: '/ventas',
        summary: 'Crear una venta con sus líneas de detalle',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cliente_id', 'sucursal_id', 'tipo_pago', 'detalles'],
                properties: [
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
                    new OA\Property(property: 'sucursal_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tipo_pago', type: 'string', enum: ['contado', 'credito'], example: 'contado'),
                    new OA\Property(property: 'dias_credito', type: 'integer', nullable: true, example: 30, description: 'Requerido si tipo_pago=credito'),
                    new OA\Property(property: 'descuento_porcentaje', type: 'number', format: 'float', example: 0),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'detalles',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(ref: '#/components/schemas/DetalleVenta'),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Venta creada', content: new OA\JsonContent(ref: '#/components/schemas/Venta')),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/Errores422')),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id'                     => 'required|exists:clientes,id',
            'sucursal_id'                    => 'required|exists:sucursales,id',
            'prima'                          => 'nullable|numeric|min:0',
            'dias_credito'                   => 'nullable|integer|min:1',
            'descuento_porcentaje'           => 'nullable|numeric|min:0|max:100',
            'observaciones'                  => 'nullable|string|max:500',
            'detalles'                       => 'required|array|min:1',
            'detalles.*.producto_id'         => 'required|exists:productos,id',
            'detalles.*.cantidad'            => 'required|integer|min:1',
            'detalles.*.precio_unitario'     => 'required|numeric|min:0',
            'detalles.*.descuento_porcentaje'=> 'nullable|numeric|min:0|max:100',
            'detalles.*.tipo_pago'           => 'nullable|in:contado,credito',
            'detalles.*.cuotas'              => 'nullable|integer|min:2',
            'detalles.*.precio_cuota'        => 'nullable|numeric|min:0',
        ]);

        $vendedor = $request->user()->vendedor;

        if (! $vendedor) {
            return response()->json(['mensaje' => 'No se encontró perfil de vendedor.'], 403);
        }

        $asignacionDetalles = collect();

        $asignacion = AsignacionDiaria::with('detalles')
            ->where('vendedor_id', $vendedor->id)
            ->where('fecha', today())
            ->where('estado', 'activa')
            ->first();

        if (! $asignacion) {
            throw ValidationException::withMessages([
                'detalles' => 'No tienes una asignacion activa para vender hoy.',
            ]);
        }

        $asignacionDetalles = $asignacion->detalles->keyBy('producto_id');
        $cantidadesSolicitadas = collect($data['detalles'])
            ->groupBy('producto_id')
            ->map(fn ($items) => $items->sum('cantidad'));

        foreach ($cantidadesSolicitadas as $productoId => $cantidadSolicitada) {
            $detalleAsignado = $asignacionDetalles->get((int) $productoId);

            if (! $detalleAsignado) {
                throw ValidationException::withMessages([
                    'detalles' => 'Uno de los productos no esta incluido en tu asignacion de hoy.',
                ]);
            }

            $cantidadVendidaHoy = DetalleVenta::where('producto_id', $productoId)
                ->whereHas('venta', function ($query) use ($vendedor) {
                    $query->where('vendedor_id', $vendedor->id)
                        ->whereDate('fecha_venta', today())
                        ->whereIn('estado', ['pendiente', 'completada']);
                })
                ->sum('cantidad');

            if (($cantidadVendidaHoy + $cantidadSolicitada) > $detalleAsignado->cantidad_asignada) {
                throw ValidationException::withMessages([
                    'detalles' => "La cantidad solicitada supera lo asignado para el producto {$detalleAsignado->producto_id}.",
                ]);
            }
        }

        $venta = DB::transaction(function () use ($data, $request, $asignacionDetalles) {
            $descuentoPct = (float) ($data['descuento_porcentaje'] ?? 0);
            $prima        = (float) ($data['prima'] ?? 0);

            // ── Preparar líneas de detalle ────────────────────────────────────────
            $subtotal    = 0;
            $detallesPrep = [];

            foreach ($data['detalles'] as $item) {
                $detalleAsignado = $asignacionDetalles->get((int) $item['producto_id']);
                $precioUnitario  = $detalleAsignado?->precio_venta ?? $item['precio_unitario'];
                $dto             = (float) ($item['descuento_porcentaje'] ?? 0);
                $linea           = round($item['cantidad'] * $precioUnitario * (1 - $dto / 100), 2);
                $subtotal       += $linea;

                // tipo_pago por línea: si no viene, se hereda del tipo general de la venta
                // (lo determinamos después de calcular totales)
                $tipoPagoLinea = $item['tipo_pago'] ?? null;

                $precioCuota = $item['precio_cuota'] ?? null;
                if (isset($item['cuotas']) && $precioCuota === null) {
                    $precioCuota = $linea / $item['cuotas'];
                }

                $detallesPrep[] = [
                    'producto_id'          => $item['producto_id'],
                    'cantidad'             => $item['cantidad'],
                    'precio_unitario'      => $precioUnitario,
                    'descuento_porcentaje' => $dto,
                    'subtotal'             => $linea,
                    'tipo_pago'            => $tipoPagoLinea,  // resolvemos abajo
                    'cuotas'               => $item['cuotas'] ?? null,
                    'precio_cuota'         => $precioCuota !== null ? round($precioCuota, 2) : null,
                ];
            }

            $descuentoMonto = round($subtotal * $descuentoPct / 100, 2);
            $total          = round($subtotal - $descuentoMonto, 2);

            // ── Determinar tipo de venta ──────────────────────────────────────────
            $tiposLinea = collect($detallesPrep)->pluck('tipo_pago')->filter()->unique()->values();

            if ($tiposLinea->count() > 1) {
                // Hay líneas contado Y crédito → mixta
                $tipoPagoVenta = 'mixta';
            } elseif ($tiposLinea->count() === 1) {
                $tipoPagoVenta = $tiposLinea->first();
            } else {
                // Ninguna línea tiene tipo_pago → usar 'contado' como default
                $tipoPagoVenta = 'contado';
            }

            // Asignar tipo_pago a las líneas que no lo tienen explícito
            $detallesPrep = array_map(function ($d) use ($tipoPagoVenta) {
                if ($d['tipo_pago'] === null) {
                    $d['tipo_pago'] = $tipoPagoVenta === 'mixta' ? 'contado' : $tipoPagoVenta;
                }
                return $d;
            }, $detallesPrep);

            // ── Calcular montos según tipo ────────────────────────────────────────
            $totalContado = collect($detallesPrep)
                ->where('tipo_pago', 'contado')
                ->sum('subtotal');

            $totalCredito = collect($detallesPrep)
                ->where('tipo_pago', 'credito')
                ->sum('subtotal');

            // Prima solo aplica al crédito; no puede ser mayor que el total crédito
            $prima = min($prima, $totalCredito);

            $montoPagado    = round($totalContado + $prima, 2);
            $saldoPendiente = round($totalCredito - $prima, 2);

            // ── Límite de crédito del cliente ─────────────────────────────────────
            // Si tiene un límite configurado (>0), esta venta no puede dejarlo con
            // más deuda de la que ese límite permite.
            if ($saldoPendiente > 0) {
                $cliente = \App\Models\Cliente::find($data['cliente_id']);

                if ($cliente && (float) $cliente->limite_credito > 0) {
                    $saldoResultante = (float) $cliente->saldo + $saldoPendiente;

                    if ($saldoResultante > (float) $cliente->limite_credito) {
                        throw ValidationException::withMessages([
                            'cliente_id' => sprintf(
                                'El cliente supera su límite de crédito ($%s). Saldo actual: $%s, esta venta agregaría $%s.',
                                number_format((float) $cliente->limite_credito, 2),
                                number_format((float) $cliente->saldo, 2),
                                number_format($saldoPendiente, 2)
                            ),
                        ]);
                    }
                }
            }

            $estaCompletada = $saldoPendiente <= 0;

            // ── Crear venta ───────────────────────────────────────────────────────
            $venta = Venta::create([
                'cliente_id'           => $data['cliente_id'],
                'sucursal_id'          => $data['sucursal_id'],
                'user_id'              => $request->user()->id,
                'vendedor_id'          => $request->user()->vendedor?->id,
                'tipo_pago'            => $tipoPagoVenta,
                'prima'                => $prima,
                'dias_credito'         => $data['dias_credito'] ?? 30,
                'fecha_pago_limite'    => $totalCredito > 0
                    ? now()->addDays($data['dias_credito'] ?? 30)->toDateString()
                    : null,
                'subtotal'             => $subtotal,
                'descuento_porcentaje' => $descuentoPct,
                'descuento_monto'      => $descuentoMonto,
                'impuesto_porcentaje'  => 0,
                'impuesto_monto'       => 0,
                'total'                => $total,
                'monto_pagado'         => $montoPagado,
                'saldo_pendiente'      => $saldoPendiente,
                'estado'               => $estaCompletada ? 'completada' : 'pendiente',
                'observaciones'        => $data['observaciones'] ?? null,
            ]);

            // ── Insertar detalles y actualizar asignación ─────────────────────────
            foreach ($detallesPrep as $d) {
                DetalleVenta::create(array_merge(['venta_id' => $venta->id], $d));

                $detalleAsignado = $asignacionDetalles->get((int) $d['producto_id']);
                if ($detalleAsignado) {
                    $detalleAsignado->increment('cantidad_vendida', $d['cantidad']);
                    $detalleAsignado->refresh();
                    $detalleAsignado->update([
                        'cantidad_devuelta' => max(0, $detalleAsignado->cantidad_asignada - $detalleAsignado->cantidad_vendida),
                    ]);
                }
            }

            // ── Crear gestiones de cobro solo para líneas a crédito ───────────────
            $lineasCredito = collect($detallesPrep)->where('tipo_pago', 'credito');

            if ($lineasCredito->isNotEmpty() && $saldoPendiente > 0) {
                // Tomar el número de cuotas del primer detalle crédito que lo tenga
                $numeroCuotas = $lineasCredito->filter(fn ($d) => $d['cuotas'])->first()['cuotas']
                    ?? $lineasCredito->first()['cuotas']
                    ?? 1;

                $montoBase = floor($saldoPendiente / $numeroCuotas * 100) / 100;
                $residuo   = round($saldoPendiente - ($montoBase * $numeroCuotas), 2);

                $gestiones = [];
                for ($i = 1; $i <= $numeroCuotas; $i++) {
                    $gestiones[] = [
                        'venta_id'         => $venta->id,
                        'cliente_id'       => $data['cliente_id'],
                        'numero_cuota'     => $i,
                        'total_cuotas'     => $numeroCuotas,
                        'monto_cuota'      => $i === $numeroCuotas
                            ? round($montoBase + $residuo, 2)
                            : $montoBase,
                        'monto_pagado'     => 0,
                        'fecha_vencimiento'=> now()->addMonths($i)->toDateString(),
                        'estado'           => 'pendiente',
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];
                }

                GestionCobro::insert($gestiones);
            }

            return $venta->load('detalles.producto:id,nombre,codigo');
        });

        return response()->json($venta, 201);
    }

    #[OA\Post(
        path: '/ventas/{id}/anular',
        summary: 'Anular una venta propia',
        description: 'Cancela una venta que todavía no tiene pagos registrados. Si la venta es de hoy y la asignación diaria del vendedor sigue activa, la cantidad se resta de "vendido" en la asignación para que pueda revenderse el mismo día. El cliente sale de su ruta de cobro si esta era su única cuenta activa (mismo efecto que cancelar desde el panel).',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['motivo'],
                properties: [
                    new OA\Property(property: 'motivo', type: 'string', example: 'El cliente se arrepintió'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Venta anulada'),
            new OA\Response(response: 404, description: 'Venta no encontrada'),
            new OA\Response(response: 422, description: 'Ya está anulada o ya tiene pagos registrados', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function anular(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $resultado = DB::transaction(function () use ($request, $id, $data) {
            $venta = Venta::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if (! $venta) {
                return ['error' => 'not_found'];
            }

            if (in_array($venta->estado, ['cancelada', 'devuelta'], true)) {
                return ['error' => 'Esta venta ya está anulada.'];
            }

            if ((float) $venta->monto_pagado > 0) {
                return ['error' => 'Esta venta ya tiene pagos registrados; no se puede anular desde la app. Contacta al administrador.'];
            }

            // Si la venta es de hoy y la asignación diaria del vendedor sigue
            // activa, se resta lo vendido para que pueda revenderse hoy mismo.
            if ($venta->vendedor_id && $venta->fecha_venta->isToday()) {
                $asignacion = AsignacionDiaria::with('detalles')
                    ->where('vendedor_id', $venta->vendedor_id)
                    ->where('fecha', today())
                    ->where('estado', 'activa')
                    ->first();

                if ($asignacion) {
                    $detallesAsignados = $asignacion->detalles->keyBy('producto_id');

                    foreach ($venta->detalles as $detalleVenta) {
                        $detalleAsignado = $detallesAsignados->get($detalleVenta->producto_id);
                        if ($detalleAsignado) {
                            $detalleAsignado->decrement('cantidad_vendida', $detalleVenta->cantidad);
                            $detalleAsignado->refresh();
                            $detalleAsignado->update([
                                'cantidad_devuelta' => max(0, $detalleAsignado->cantidad_asignada - $detalleAsignado->cantidad_vendida),
                            ]);
                        }
                    }
                }
            }

            $venta->update([
                'estado'        => 'cancelada',
                'observaciones' => trim(($venta->observaciones ? $venta->observaciones . ' | ' : '') . 'Anulada: ' . $data['motivo']),
            ]);

            return ['venta' => $venta];
        });

        if (isset($resultado['error'])) {
            return $resultado['error'] === 'not_found'
                ? response()->json(['mensaje' => 'Venta no encontrada.'], 404)
                : response()->json(['mensaje' => $resultado['error']], 422);
        }

        return response()->json([
            'mensaje' => 'Venta anulada.',
            'venta'   => [
                'id'            => $resultado['venta']->id,
                'numero_venta'  => $resultado['venta']->numero_venta,
                'estado'        => $resultado['venta']->estado,
            ],
        ]);
    }
}
