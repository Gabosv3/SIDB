<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GestionCobro;
use App\Models\Reintegro;
use App\Models\Vendedor;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReintegroController extends Controller
{
    /**
     * Sucursal del usuario autenticado, ya sea por su perfil de vendedor o de
     * cobrador (un cobrador no tiene "vendedor", pero sí sucursal propia).
     */
    private function sucursalIdDelUsuario($user): ?int
    {
        return $user->vendedor?->sucursal_id ?? $user->cobrador?->sucursal_id;
    }

    #[OA\Get(
        path: '/reintegros/candidatos',
        summary: 'Ventas candidatas a reintegro por cuotas vencidas',
        description: 'Devuelve ventas con cuotas vencidas hace N días (default 30), con los productos de la venta y datos del cliente. Filtra por sucursal del usuario autenticado.',
        security: [['sanctum' => []]],
        tags: ['Reintegros'],
        parameters: [
            new OA\Parameter(name: 'dias_vencidos', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 30), description: 'Días mínimos de vencimiento para considerar candidato'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 30)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de candidatos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer'),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'venta_id', type: 'integer'),
                                    new OA\Property(property: 'numero_venta', type: 'string'),
                                    new OA\Property(property: 'fecha_venta', type: 'string', format: 'date'),
                                    new OA\Property(property: 'saldo_pendiente', type: 'number'),
                                    new OA\Property(property: 'cuotas_vencidas', type: 'integer'),
                                    new OA\Property(property: 'monto_adeudado_vencido', type: 'number'),
                                    new OA\Property(property: 'ya_en_reintegro', type: 'boolean'),
                                    new OA\Property(property: 'cliente', type: 'object'),
                                    new OA\Property(property: 'productos', type: 'array', items: new OA\Items(type: 'object')),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function candidatos(Request $request): JsonResponse
    {
        $diasVencidas = $request->integer('dias_vencidos', 30);
        $sucursalId   = $this->sucursalIdDelUsuario($request->user());

        $corte = now()->subDays($diasVencidas)->toDateString();

        // Ventas con cuotas vencidas y saldo pendiente
        $ventas = Venta::query()
            ->with([
                'cliente:id,nombre,apellido,dui,codigo_anterior,telefono_normal,direccion,municipio',
                'detalles.producto:id,nombre,codigo',
            ])
            ->when($sucursalId, fn ($q) => $q->where('ventas.sucursal_id', $sucursalId))
            ->where('saldo_pendiente', '>', 0)
            ->whereHas('gestionesCobro', fn ($g) =>
                $g->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                  ->whereDate('fecha_vencimiento', '<=', $corte)
            )
            ->withCount([
                'gestionesCobro as cuotas_vencidas' => fn ($g) =>
                    $g->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                      ->whereDate('fecha_vencimiento', '<=', $corte),
            ])
            ->selectRaw('ventas.*, (
                SELECT COALESCE(SUM(monto_cuota - monto_pagado), 0)
                FROM gestion_cobros
                WHERE gestion_cobros.venta_id = ventas.id
                  AND gestion_cobros.estado IN (\'pendiente\', \'parcialmente_cobrado\')
                  AND DATE(gestion_cobros.fecha_vencimiento) <= ?
            ) as monto_adeudado_vencido', [$corte])
            ->orderByDesc('cuotas_vencidas')
            ->paginate($request->integer('per_page', 30));

        $ventas->getCollection()->transform(function ($v) {
            return [
                'venta_id'      => $v->id,
                'numero_venta'  => $v->numero_venta,
                'fecha_venta'   => $v->fecha_venta?->toDateString(),
                'saldo_pendiente' => (float) $v->saldo_pendiente,
                'cuotas_vencidas' => (int) $v->cuotas_vencidas,
                'monto_adeudado_vencido' => round((float) $v->monto_adeudado_vencido, 2),
                'ya_en_reintegro' => Reintegro::where('venta_id', $v->id)
                    ->whereIn('estado', ['pendiente', 'en_proceso'])
                    ->exists(),
                'cliente' => $v->cliente ? [
                    'id'              => $v->cliente->id,
                    'nombre'          => $v->cliente->nombre_completo,
                    'dui'             => $v->cliente->dui,
                    'codigo_anterior' => $v->cliente->codigo_anterior,
                    'telefono'        => $v->cliente->telefono_normal,
                    'direccion'       => trim(($v->cliente->direccion ?? '') . ', ' . ($v->cliente->municipio ?? ''), ', '),
                ] : null,
                'productos' => $v->detalles->map(fn ($d) => [
                    'id'       => $d->producto?->id,
                    'codigo'   => $d->producto?->codigo,
                    'nombre'   => $d->producto?->nombre,
                    'cantidad' => (float) $d->cantidad,
                ])->values(),
            ];
        });

        return response()->json($ventas);
    }

    #[OA\Get(
        path: '/reintegros/vendedores',
        summary: 'Listar vendedores disponibles para asignar reintegro',
        security: [['sanctum' => []]],
        tags: ['Reintegros'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de vendedores activos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'vendedores',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'codigo', type: 'string'),
                                    new OA\Property(property: 'nombre', type: 'string'),
                                    new OA\Property(property: 'telefono', type: 'string'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function vendedores(Request $request): JsonResponse
    {
        $sucursalId = $this->sucursalIdDelUsuario($request->user());

        $vendedores = Vendedor::query()
            ->where('activo', true)
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->select('id', 'codigo', 'nombre', 'apellido', 'telefono')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($v) => [
                'id'      => $v->id,
                'codigo'  => $v->codigo,
                'nombre'  => $v->nombre . ' ' . $v->apellido,
                'telefono' => $v->telefono,
            ]);

        return response()->json(['vendedores' => $vendedores]);
    }

    #[OA\Post(
        path: '/reintegros',
        summary: 'Crear reintegro sin asignación',
        description: 'Registra una venta para reintegro. Queda sin vendedor asignado (pendiente). Un administrador asigna el vendedor desde el panel Filament.',
        security: [['sanctum' => []]],
        tags: ['Reintegros'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['venta_id'],
                properties: [
                    new OA\Property(property: 'venta_id', type: 'integer', example: 12, description: 'ID de la venta a recuperar'),
                    new OA\Property(property: 'motivo', type: 'string', example: '4 cuotas sin pagar, cliente no contesta'),
                    new OA\Property(property: 'observaciones', type: 'string', example: 'Verificar que los productos estén completos'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Reintegro creado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'mensaje', type: 'string'),
                        new OA\Property(
                            property: 'reintegro',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'venta_id', type: 'integer'),
                                new OA\Property(property: 'numero_venta', type: 'string'),
                                new OA\Property(property: 'cliente', type: 'string'),
                                new OA\Property(property: 'vendedor', type: 'string'),
                                new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'en_proceso', 'recuperado', 'no_recuperado']),
                                new OA\Property(property: 'monto_adeudado', type: 'number'),
                                new OA\Property(property: 'cuotas_vencidas', type: 'integer'),
                                new OA\Property(property: 'fecha_asignacion', type: 'string', format: 'date'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Sin ruta asignada, reintegro ya activo, o venta sin saldo pendiente'),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'venta_id'      => 'required|integer|exists:ventas,id',
            'motivo'        => 'nullable|string|max:500',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $venta = Venta::with('cliente')->findOrFail($data['venta_id']);

        if ($venta->saldo_pendiente <= 0) {
            return response()->json(['message' => 'Esta venta no tiene saldo pendiente.'], 422);
        }

        $existente = Reintegro::where('venta_id', $venta->id)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->first();

        if ($existente) {
            return response()->json([
                'message'      => 'Esta venta ya tiene un reintegro activo.',
                'reintegro_id' => $existente->id,
                'estado'       => $existente->estado,
            ], 422);
        }

        $corte = now()->toDateString();
        $stats = GestionCobro::where('venta_id', $venta->id)
            ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
            ->whereDate('fecha_vencimiento', '<=', $corte)
            ->selectRaw('COUNT(*) as cuotas_vencidas, COALESCE(SUM(monto_cuota - monto_pagado), 0) as monto_adeudado')
            ->first();

        $reintegro = Reintegro::create([
            'venta_id'         => $venta->id,
            'cliente_id'       => $venta->cliente_id,
            'vendedor_id'      => null,
            'sucursal_id'      => $venta->sucursal_id,
            'asignado_por'     => $request->user()->id,
            'estado'           => 'pendiente',
            'motivo'           => $data['motivo'] ?? null,
            'observaciones'    => $data['observaciones'] ?? null,
            'fecha_asignacion' => now()->toDateString(),
            'monto_adeudado'   => $stats->monto_adeudado ?? 0,
            'cuotas_vencidas'  => $stats->cuotas_vencidas ?? 0,
        ]);

        return response()->json([
            'mensaje'   => 'Reintegro registrado. Pendiente de asignación por el administrador.',
            'reintegro' => [
                'id'               => $reintegro->id,
                'venta_id'         => $reintegro->venta_id,
                'numero_venta'     => $venta->numero_venta,
                'cliente'          => $venta->cliente?->nombre_completo,
                'vendedor'         => null,
                'estado'           => $reintegro->estado,
                'monto_adeudado'   => (float) $reintegro->monto_adeudado,
                'cuotas_vencidas'  => (int) $reintegro->cuotas_vencidas,
                'fecha_asignacion' => $reintegro->fecha_asignacion->toDateString(),
            ],
        ], 201);
    }

    #[OA\Get(
        path: '/reintegros',
        summary: 'Listar reintegros del vendedor autenticado',
        security: [['sanctum' => []]],
        tags: ['Reintegros'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pendiente', 'en_proceso', 'recuperado', 'no_recuperado'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada de reintegros'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $user     = $request->user();
        $vendedor = $user->vendedor;
        $cobrador = $user->cobrador;

        $query = Reintegro::query()
            ->with([
                'venta:id,numero_venta,total,saldo_pendiente',
                'cliente:id,nombre,apellido,dui,telefono_normal,direccion,latitud,longitud',
                'vendedor:id,nombre,apellido,codigo',
            ])
            ->where(function ($q) use ($vendedor, $cobrador) {
                // Un vendedor ve los reintegros que le asignaron. Un cobrador (que
                // no tiene vendedor_id propio en el reintegro) ve los de su
                // sucursal. Sin ninguno de los dos perfiles, no ve nada.
                if ($vendedor) {
                    $q->orWhere('vendedor_id', $vendedor->id);
                }

                if ($cobrador) {
                    $q->orWhere('sucursal_id', $cobrador->sucursal_id);
                }

                if (! $vendedor && ! $cobrador) {
                    $q->whereRaw('1 = 0');
                }
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado));

        $reintegros = $query->latest()->paginate($request->integer('per_page', 20));

        $reintegros->getCollection()->transform(fn ($r) => [
            'id'              => $r->id,
            'estado'          => $r->estado,
            'monto_adeudado'  => (float) $r->monto_adeudado,
            'cuotas_vencidas' => (int) $r->cuotas_vencidas,
            'fecha_asignacion'   => $r->fecha_asignacion?->toDateString(),
            'fecha_recuperacion' => $r->fecha_recuperacion?->toDateString(),
            'motivo'          => $r->motivo,
            'observaciones'   => $r->observaciones,
            'venta'           => $r->venta ? [
                'id'             => $r->venta->id,
                'numero_venta'   => $r->venta->numero_venta,
                'saldo_pendiente' => (float) $r->venta->saldo_pendiente,
            ] : null,
            'cliente'         => $r->cliente ? [
                'id'      => $r->cliente->id,
                'nombre'  => $r->cliente->nombre_completo,
                'dui'     => $r->cliente->dui,
                'telefono' => $r->cliente->telefono_normal,
                'direccion' => $r->cliente->direccion,
                'latitud'  => $r->cliente->latitud !== null ? (float) $r->cliente->latitud : null,
                'longitud' => $r->cliente->longitud !== null ? (float) $r->cliente->longitud : null,
            ] : null,
            'vendedor'        => $r->vendedor ? [
                'id'     => $r->vendedor->id,
                'nombre' => $r->vendedor->nombre . ' ' . $r->vendedor->apellido,
                'codigo' => $r->vendedor->codigo,
            ] : null,
        ]);

        return response()->json($reintegros);
    }

    #[OA\Patch(
        path: '/reintegros/{id}/estado',
        summary: 'Actualizar estado de un reintegro',
        description: 'Cambia el estado luego de la visita al cliente. Al marcar recuperado o no_recuperado se registra la fecha de recuperación automáticamente.',
        security: [['sanctum' => []]],
        tags: ['Reintegros'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['estado'],
                properties: [
                    new OA\Property(property: 'estado', type: 'string', enum: ['en_proceso', 'recuperado', 'no_recuperado'], example: 'recuperado'),
                    new OA\Property(property: 'observaciones', type: 'string', example: 'Se recogieron todos los productos en buen estado'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estado actualizado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'mensaje', type: 'string'),
                        new OA\Property(
                            property: 'reintegro',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'estado', type: 'string'),
                                new OA\Property(property: 'fecha_recuperacion', type: 'string', format: 'date', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Reintegro no encontrado'),
            new OA\Response(response: 422, description: 'Estado inválido'),
        ],
    )]
    public function actualizarEstado(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'estado'       => 'required|in:en_proceso,recuperado,no_recuperado',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $reintegro = Reintegro::findOrFail($id);

        $vendedor = $request->user()->vendedor;
        if (! $vendedor || $reintegro->vendedor_id !== $vendedor->id) {
            return response()->json(['mensaje' => 'Este reintegro no te pertenece.'], 403);
        }

        $reintegro->estado       = $data['estado'];
        $reintegro->observaciones = $data['observaciones'] ?? $reintegro->observaciones;

        if (in_array($data['estado'], ['recuperado', 'no_recuperado'])) {
            $reintegro->fecha_recuperacion = now()->toDateString();
        }

        $reintegro->save();

        return response()->json([
            'mensaje'   => 'Estado actualizado.',
            'reintegro' => [
                'id'              => $reintegro->id,
                'estado'          => $reintegro->estado,
                'fecha_recuperacion' => $reintegro->fecha_recuperacion?->toDateString(),
            ],
        ]);
    }
}
