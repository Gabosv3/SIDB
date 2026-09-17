<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsignacionDiaria;
use App\Models\DetalleVenta;
use App\Models\GestionCobro;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\IdempotencyService;
use App\Services\VentaCorreccionService;
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
        $query = Venta::with([
            'cliente:id,nombre,apellido,telefono_normal,telefono_whatsapp',
            'detalles.producto:id,nombre,codigo',
            'vendedor:id,nombre,apellido',
            'user:id,name',
        ])
            ->where('user_id', $request->user()->id);

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->integer('sucursal_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Todas las ventas de este listado son del mismo vendedor (filtrado
        // arriba por user_id), así que si tiene alias configurado se aplica
        // igual a todas — sustituye el nombre que ve el ticket sin tocar el
        // nombre real de la cuenta.
        $alias = $request->user()->alias;
        $ventas = $query->latest('fecha_venta')->paginate($request->integer('per_page', 20));

        if ($alias) {
            $ventas->getCollection()->each(fn (Venta $v) => self::aplicarAliasVendedor($v, $alias));
        }

        return response()->json($ventas);
    }

    // Sustituye el nombre del vendedor/usuario en la venta cargada por su
    // alias, si tiene uno — solo afecta lo que devuelve esta respuesta, no
    // toca la base de datos.
    private static function aplicarAliasVendedor(Venta $venta, string $alias): void
    {
        if ($venta->relationLoaded('vendedor') && $venta->vendedor) {
            $venta->vendedor->setAttribute('nombre', $alias);
            $venta->vendedor->setAttribute('apellido', '');
        }
        if ($venta->relationLoaded('user') && $venta->user) {
            $venta->user->setAttribute('name', $alias);
        }
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
        description: 'Solo vendedores con una asignación diaria activa para hoy. El tipo de pago de la venta '
            .'(contado/crédito/mixta) se calcula automáticamente a partir del `tipo_pago` de cada línea de '
            .'`detalles`, no se envía a nivel de venta. Cada línea de producto debe estar dentro de la '
            .'asignación diaria del vendedor y no superar la cantidad asignada (sumando lo ya vendido hoy).',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cliente_id', 'sucursal_id', 'detalles'],
                properties: [
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
                    new OA\Property(property: 'sucursal_id', type: 'integer', example: 1),
                    new OA\Property(property: 'prima', type: 'number', format: 'float', nullable: true, example: 0, description: 'Abono inicial en ventas a crédito. Puede superar el total a crédito (queda registrada tal cual); el saldo_pendiente nunca baja de 0'),
                    new OA\Property(property: 'dias_credito', type: 'integer', nullable: true, example: 30, description: 'Días para fecha_pago_limite si hay líneas a crédito (default 30)'),
                    new OA\Property(property: 'descuento_porcentaje', type: 'number', format: 'float', nullable: true, example: 0),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true, maxLength: 500),
                    new OA\Property(property: 'idempotency_key', type: 'string', nullable: true, maxLength: 80, description: 'Generada una vez por la app antes del primer intento y reutilizada en reintentos offline, para que un timeout no cree una venta duplicada'),
                    new OA\Property(
                        property: 'detalles',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(
                            required: ['producto_id', 'cantidad', 'precio_unitario'],
                            properties: [
                                new OA\Property(property: 'producto_id', type: 'integer', example: 5),
                                new OA\Property(property: 'cantidad', type: 'integer', minimum: 1, example: 2),
                                new OA\Property(property: 'precio_unitario', type: 'number', format: 'float', example: 15.0),
                                new OA\Property(property: 'descuento_porcentaje', type: 'number', format: 'float', nullable: true, example: 0),
                                new OA\Property(property: 'tipo_pago', type: 'string', enum: ['contado', 'credito'], nullable: true, description: 'Si se omite en TODAS las líneas, la venta es contado por defecto'),
                                new OA\Property(property: 'cuotas', type: 'integer', minimum: 2, nullable: true, description: 'Solo para líneas a crédito'),
                                new OA\Property(property: 'precio_cuota', type: 'number', format: 'float', nullable: true, description: 'Si se omite y hay cuotas, se calcula como subtotal de la línea / cuotas'),
                            ],
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Venta creada', content: new OA\JsonContent(ref: '#/components/schemas/Venta')),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'El usuario no tiene perfil de vendedor'),
            new OA\Response(response: 422, description: 'Validación fallida, sin asignación diaria activa, producto fuera de asignación, cantidad excedida, o cliente supera su límite de crédito', content: new OA\JsonContent(ref: '#/components/schemas/Errores422')),
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
            'idempotency_key'                => 'nullable|string|max:80',
        ]);

        return IdempotencyService::manejar(
            $request->user()->id,
            'ventas.store',
            $data['idempotency_key'] ?? null,
            fn () => $this->crearVenta($request, $data)
        );
    }

    private function crearVenta(Request $request, array $data): JsonResponse
    {
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

            [
                'detallesPrep'   => $detallesPrep,
                'subtotal'       => $subtotal,
                'descuentoMonto' => $descuentoMonto,
                'total'          => $total,
                'tipoPagoVenta'  => $tipoPagoVenta,
                'montoPagado'    => $montoPagado,
                'saldoPendiente' => $saldoPendiente,
                'totalCredito'   => $totalCredito,
            ] = VentaCorreccionService::calcularDetallesYTotales($data['detalles'], $descuentoPct, $prima, $asignacionDetalles);

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

            return $venta->load([
                'detalles.producto:id,nombre,codigo',
                'vendedor:id,nombre,apellido',
                'user:id,name',
            ]);
        });

        if ($request->user()->alias) {
            self::aplicarAliasVendedor($venta, $request->user()->alias);
        }

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
    #[OA\Patch(
        path: '/ventas/{id}',
        summary: 'Corregir una venta del mismo día (prima, productos, cliente)',
        description: 'Solo el vendedor que la hizo, solo el mismo día, y solo si todavía no tiene ningún abono registrado aparte de la prima inicial. Para ventas de días anteriores o con abonos ya cobrados, la corrección la hace un administrador desde el panel.',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Venta corregida'),
            new OA\Response(response: 404, description: 'Venta no encontrada'),
            new OA\Response(response: 422, description: 'No se puede corregir'),
        ],
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'motivo'                          => 'required|string|max:500',
            'cliente_id'                       => 'sometimes|integer|exists:clientes,id',
            'prima'                            => 'nullable|numeric|min:0',
            'descuento_porcentaje'             => 'nullable|numeric|min:0|max:100',
            'detalles'                         => 'sometimes|array|min:1',
            'detalles.*.producto_id'           => 'required_with:detalles|integer|exists:productos,id',
            'detalles.*.cantidad'              => 'required_with:detalles|integer|min:1',
            'detalles.*.precio_unitario'       => 'required_with:detalles|numeric|min:0',
            'detalles.*.descuento_porcentaje'  => 'nullable|numeric|min:0|max:100',
            'detalles.*.tipo_pago'             => 'nullable|in:contado,credito',
            'detalles.*.cuotas'                => 'nullable|integer|min:2',
            'detalles.*.precio_cuota'          => 'nullable|numeric|min:0',
        ]);

        $vendedor = $request->user()->vendedor;
        if (! $vendedor) {
            return response()->json(['mensaje' => 'No se encontró perfil de vendedor.'], 403);
        }

        $venta = Venta::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('detalles')
            ->first();

        if (! $venta) {
            return response()->json(['mensaje' => 'Venta no encontrada.'], 404);
        }

        if (! $venta->fecha_venta->isToday()) {
            return response()->json(['mensaje' => 'Solo puedes corregir ventas del mismo día. Para una venta de un día anterior, pide a un administrador que la corrija desde el panel.'], 422);
        }

        $asignacion = AsignacionDiaria::with('detalles')
            ->where('vendedor_id', $vendedor->id)
            ->where('fecha', today())
            ->where('estado', 'activa')
            ->first();

        if (! $asignacion) {
            return response()->json(['mensaje' => 'No tienes una asignación activa hoy — no se puede recalcular el stock para corregir la venta.'], 422);
        }

        $nuevosDetalles = $data['detalles'] ?? $venta->detalles->map(fn ($d) => [
            'producto_id'          => $d->producto_id,
            'cantidad'             => $d->cantidad,
            'precio_unitario'      => (float) $d->precio_unitario,
            'descuento_porcentaje' => (float) $d->descuento_porcentaje,
            'tipo_pago'            => $d->tipo_pago,
            'cuotas'               => $d->cuotas,
            'precio_cuota'         => $d->precio_cuota !== null ? (float) $d->precio_cuota : null,
        ])->toArray();

        $descuentoPct = array_key_exists('descuento_porcentaje', $data) ? (float) $data['descuento_porcentaje'] : (float) $venta->descuento_porcentaje;
        $prima        = array_key_exists('prima', $data) ? (float) $data['prima'] : (float) $venta->prima;
        $clienteId    = $data['cliente_id'] ?? $venta->cliente_id;

        $resultado = VentaCorreccionService::aplicarCorreccion(
            $venta, $nuevosDetalles, $prima, $descuentoPct, $clienteId, $asignacion, $data['motivo'], $id
        );

        if (isset($resultado['error'])) {
            return response()->json(['mensaje' => $resultado['error']], 422);
        }

        if ($request->user()->alias) {
            self::aplicarAliasVendedor($resultado['venta'], $request->user()->alias);
        }

        return response()->json($resultado['venta']);
    }

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
