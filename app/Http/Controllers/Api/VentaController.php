<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsignacionDiaria;
use App\Models\DetalleVenta;
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
    public function show(int $id): JsonResponse
    {
        $venta = Venta::with([
            'cliente:id,nombre,apellido,dui,telefono_normal',
            'detalles.producto:id,nombre,codigo,unidad_medida',
            'pagos:id,venta_id,monto,fecha_pago,metodo_pago',
        ])->findOrFail($id);

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
            'cliente_id'            => 'required|exists:clientes,id',
            'sucursal_id'           => 'required|exists:sucursales,id',
            'tipo_pago'             => 'required|in:contado,credito',
            'dias_credito'          => 'required_if:tipo_pago,credito|nullable|integer|min:1',
            'descuento_porcentaje'  => 'nullable|numeric|min:0|max:100',
            'observaciones'         => 'nullable|string|max:500',
            'detalles'              => 'required|array|min:1',
            'detalles.*.producto_id'         => 'required|exists:productos,id',
            'detalles.*.cantidad'            => 'required|integer|min:1',
            'detalles.*.precio_unitario'     => 'required|numeric|min:0',
            'detalles.*.descuento_porcentaje'=> 'nullable|numeric|min:0|max:100',
            'detalles.*.cuotas'              => 'nullable|integer|min:2',
            'detalles.*.precio_cuota'        => 'nullable|numeric|min:0',
        ]);

        $asignacionDetalles = collect();
        $vendedor = $request->user()->vendedor;

        if ($vendedor) {
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
        }

        $venta = DB::transaction(function () use ($data, $request, $asignacionDetalles) {
            $descuentoPct = (float) ($data['descuento_porcentaje'] ?? 0);

            // Calcular subtotal de líneas
            $subtotal = 0;
            $detallesPrep = [];
            foreach ($data['detalles'] as $item) {
                $detalleAsignado = $asignacionDetalles->get((int) $item['producto_id']);
                $precioUnitario = $detalleAsignado?->precio_venta ?? $item['precio_unitario'];
                $dto = (float) ($item['descuento_porcentaje'] ?? 0);
                $linea = round($item['cantidad'] * $precioUnitario * (1 - $dto / 100), 2);
                $subtotal += $linea;
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
                    'cuotas'               => $item['cuotas'] ?? null,
                    'precio_cuota'         => $precioCuota !== null ? round($precioCuota, 2) : null,
                ];
            }

            $descuentoMonto = round($subtotal * $descuentoPct / 100, 2);
            $total          = round($subtotal - $descuentoMonto, 2);

            $venta = Venta::create([
                'cliente_id'           => $data['cliente_id'],
                'sucursal_id'          => $data['sucursal_id'],
                'user_id'              => $request->user()->id,
                'vendedor_id'          => $request->user()->vendedor?->id,
                'tipo_pago'            => $data['tipo_pago'],
                'dias_credito'         => $data['dias_credito'] ?? 0,
                'fecha_pago_limite'    => $data['tipo_pago'] === 'credito'
                    ? now()->addDays($data['dias_credito'] ?? 30)->toDateString()
                    : null,
                'subtotal'             => $subtotal,
                'descuento_porcentaje' => $descuentoPct,
                'descuento_monto'      => $descuentoMonto,
                'impuesto_porcentaje'  => 0,
                'impuesto_monto'       => 0,
                'total'                => $total,
                'monto_pagado'         => $data['tipo_pago'] === 'contado' ? $total : 0,
                'saldo_pendiente'      => $data['tipo_pago'] === 'contado' ? 0 : $total,
                'estado'               => $data['tipo_pago'] === 'contado' ? 'completada' : 'pendiente',
                'observaciones'        => $data['observaciones'] ?? null,
            ]);

            foreach ($detallesPrep as $d) {
                DetalleVenta::create(array_merge(['venta_id' => $venta->id], $d));

                $detalleAsignado = $asignacionDetalles->get((int) $d['producto_id']);
                if ($detalleAsignado) {
                    $detalleAsignado->increment('cantidad_vendida', $d['cantidad']);
                    $detalleAsignado->update([
                        'cantidad_devuelta' => max(0, $detalleAsignado->cantidad_asignada - $detalleAsignado->cantidad_vendida),
                    ]);
                }
            }

            return $venta->load('detalles.producto:id,nombre,codigo');
        });

        return response()->json($venta, 201);
    }
}
