<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\Vale;
use App\Models\VisitaCobro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class CobroController extends Controller
{
    private function diaHoy(): string
    {
        $dias = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
        return $dias[now()->dayOfWeekIso];
    }

    private function cobrador(Request $request)
    {
        $user = $request->user();
        $cobrador = $user->cobrador;

        if (! $cobrador) {
            $vendedor = $user->vendedor;
            if ($vendedor && $vendedor->es_cobrador) {
                $cobrador = Cobrador::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'sucursal_id' => $vendedor->sucursal_id,
                        'nombre'      => $vendedor->nombre,
                        'apellido'    => $vendedor->apellido,
                        'telefono'    => $vendedor->telefono,
                        'email'       => $vendedor->email,
                        'activo'      => true,
                    ]
                );
            }
        }

        return $cobrador;
    }

    private function clienteDeRuta(int $clienteId, $cobrador): ?\App\Models\Cliente
    {
        $rutasIds = $cobrador->rutasCobro()->pluck('id');
        return \App\Models\Cliente::where('id', $clienteId)
            ->whereIn('ruta_cobro_id', $rutasIds)
            ->first();
    }

    // ── GET /cobros/ruta-hoy ────────────────────────────────────────────────

    #[OA\Get(
        path: '/cobros/ruta-hoy',
        summary: 'Obtener rutas del cobrador para hoy',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        responses: [
            new OA\Response(response: 200, description: 'Rutas del día con conteo de clientes'),
            new OA\Response(response: 404, description: 'Sin ruta para hoy'),
        ],
    )]
    public function rutaHoy(Request $request): JsonResponse
    {
        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }
        $diaHoy = $this->diaHoy();

        $rutas = $cobrador->rutasCobro()
            ->where('dia_semana', $diaHoy)
            ->where('activa', true)
            ->withCount('clientes')
            ->with([
                'clientes' => fn ($q) => $q
                    ->select('id', 'nombre', 'apellido', 'dui', 'codigo_anterior', 'telefono_normal', 'saldo', 'latitud', 'longitud', 'ruta_cobro_id', 'orden')
                    ->orderByRaw('orden IS NULL, orden')
                    ->with(['ventas' => fn ($v) => $v
                        ->select('id', 'cliente_id', 'numero_venta', 'total', 'monto_pagado', 'saldo_pendiente', 'estado', 'fecha_venta')
                        ->where('saldo_pendiente', '>', 0)
                        ->withCount([
                            'gestionesCobro as cuotas_pendientes' => fn ($g) => $g
                                ->whereIn('estado', ['pendiente', 'parcialmente_cobrado']),
                            'gestionesCobro as cuotas_vencidas' => fn ($g) => $g
                                ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                                ->whereDate('fecha_vencimiento', '<', today()),
                        ])
                    ])
                    ->addSelect([
                        'cuotas_vencidas' => GestionCobro::selectRaw('COUNT(*)')
                            ->whereColumn('cliente_id', 'clientes.id')
                            ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                            ->whereDate('fecha_vencimiento', '<', today()),
                        'para_estar_al_dia' => GestionCobro::selectRaw('COALESCE(SUM(monto_cuota - monto_pagado), 0)')
                            ->whereColumn('cliente_id', 'clientes.id')
                            ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                            ->whereDate('fecha_vencimiento', '<', today()),
                    ]),
            ])
            ->get();

        if ($rutas->isEmpty()) {
            return response()->json([
                'mensaje' => 'No hay ruta asignada para hoy (' . ucfirst($diaHoy) . ').',
                'dia' => $diaHoy,
            ], 404);
        }

        return response()->json([
            'dia' => $diaHoy,
            'rutas' => $rutas->map(fn ($ruta) => [
                'id' => $ruta->id,
                'nombre' => $ruta->nombre,
                'dia_semana' => $ruta->dia_semana,
                'total_clientes' => $ruta->clientes_count,
                'clientes' => $ruta->clientes->map(fn ($c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre_completo,
                    'dui' => $c->dui,
                    'codigo_anterior' => $c->codigo_anterior,
                    'telefono' => $c->telefono_normal,
                    'saldo_total' => (float) $c->saldo,
                    'cuotas_vencidas' => (int) $c->cuotas_vencidas,
                    'para_estar_al_dia' => round((float) $c->para_estar_al_dia, 2),
                    'ubicacion' => [
                        'latitud' => (float) $c->latitud,
                        'longitud' => (float) $c->longitud,
                    ],
                    'ventas' => $c->ventas->map(fn ($v) => [
                        'id'              => $v->id,
                        'numero_venta'    => $v->numero_venta,
                        'fecha_venta'     => $v->fecha_venta instanceof \Carbon\Carbon
                            ? $v->fecha_venta->format('d/m/Y')
                            : \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y'),
                        'total'           => (float) $v->total,
                        'monto_pagado'    => (float) $v->monto_pagado,
                        'saldo_pendiente' => (float) $v->saldo_pendiente,
                        'cuotas_pendientes' => (int) $v->cuotas_pendientes,
                        'cuotas_vencidas'   => (int) $v->cuotas_vencidas,
                    ])->values(),
                ]),
            ]),
        ]);
    }

    // ── GET /cobros/rutas/{ruta_id}/clientes ─────────────────────────────────

    #[OA\Get(
        path: '/cobros/rutas/{ruta_id}/clientes',
        summary: 'Listar clientes de una ruta específica',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'ruta_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Clientes de la ruta'),
            new OA\Response(response: 403, description: 'Esta ruta no te pertenece'),
            new OA\Response(response: 404, description: 'Ruta no encontrada'),
        ],
    )]
    public function clientesPorRuta(Request $request, int $rutaId): JsonResponse
    {
        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $ruta = $cobrador->rutasCobro()
            ->where('id', $rutaId)
            ->where('activa', true)
            ->first();

        if (! $ruta) {
            return response()->json(['mensaje' => 'Ruta no encontrada o no te pertenece.'], 403);
        }

        $clientes = $ruta->clientes()
            ->where('activo', true)
            ->select('id', 'nombre', 'apellido', 'dui', 'codigo_anterior', 'telefono_normal', 'telefono_whatsapp', 'saldo', 'direccion', 'municipio', 'departamento', 'latitud', 'longitud', 'ruta_cobro_id', 'orden')
            ->orderByRaw('orden IS NULL, orden')
            ->with(['ventas' => fn ($v) => $v
                ->select('id', 'cliente_id', 'numero_venta', 'total', 'monto_pagado', 'saldo_pendiente', 'estado', 'fecha_venta')
                ->where('saldo_pendiente', '>', 0)
                ->withCount([
                    'gestionesCobro as cuotas_pendientes' => fn ($g) => $g
                        ->whereIn('estado', ['pendiente', 'parcialmente_cobrado']),
                    'gestionesCobro as cuotas_vencidas' => fn ($g) => $g
                        ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                        ->whereDate('fecha_vencimiento', '<', today()),
                ])
            ])
            ->addSelect([
                'cuotas_vencidas' => GestionCobro::selectRaw('COUNT(*)')
                    ->whereColumn('cliente_id', 'clientes.id')
                    ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                    ->whereDate('fecha_vencimiento', '<', today()),
                'para_estar_al_dia' => GestionCobro::selectRaw('COALESCE(SUM(monto_cuota - monto_pagado), 0)')
                    ->whereColumn('cliente_id', 'clientes.id')
                    ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                    ->whereDate('fecha_vencimiento', '<', today()),
            ])
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre_completo,
                'dui' => $c->dui,
                'codigo_anterior' => $c->codigo_anterior,
                'telefono' => $c->telefono_normal,
                'whatsapp' => $c->telefono_whatsapp,
                'saldo_total' => (float) $c->saldo,
                'cuotas_vencidas' => (int) $c->cuotas_vencidas,
                'para_estar_al_dia' => round((float) $c->para_estar_al_dia, 2),
                'direccion' => "{$c->direccion}, {$c->municipio}, {$c->departamento}",
                'ubicacion' => [
                    'latitud' => (float) $c->latitud,
                    'longitud' => (float) $c->longitud,
                ],
                'ventas' => $c->ventas->map(fn ($v) => [
                    'id'              => $v->id,
                    'numero_venta'    => $v->numero_venta,
                    'fecha_venta'     => $v->fecha_venta instanceof \Carbon\Carbon
                        ? $v->fecha_venta->format('d/m/Y')
                        : \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y'),
                    'total'           => (float) $v->total,
                    'monto_pagado'    => (float) $v->monto_pagado,
                    'saldo_pendiente' => (float) $v->saldo_pendiente,
                    'cuotas_pendientes' => (int) $v->cuotas_pendientes,
                    'cuotas_vencidas'   => (int) $v->cuotas_vencidas,
                ])->values(),
            ]);

        return response()->json([
            'ruta' => ['id' => $ruta->id, 'nombre' => $ruta->nombre],
            'total' => $clientes->count(),
            'clientes' => $clientes,
        ]);
    }

    // ── GET /cobros/rutas/{ruta_id}/orden ─────────────────────────────────────

    #[OA\Get(
        path: '/cobros/rutas/{ruta_id}/orden',
        summary: 'Orden guardado de los clientes de una ruta',
        description: 'Devuelve los IDs de cliente en el orden en que el cobrador los dejó la última vez '
            .'(persistido en el servidor, sobrevive reinstalaciones de la app).',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'ruta_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'IDs de cliente en orden'),
            new OA\Response(response: 403, description: 'Ruta no encontrada o no te pertenece'),
        ],
    )]
    public function ordenClientes(Request $request, int $rutaId): JsonResponse
    {
        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $ruta = $cobrador->rutasCobro()->where('id', $rutaId)->first();
        if (! $ruta) {
            return response()->json(['mensaje' => 'Ruta no encontrada o no te pertenece.'], 403);
        }

        $ids = $ruta->clientes()->where('activo', true)->orderByRaw('orden IS NULL, orden')->pluck('id');

        return response()->json(['ids' => $ids]);
    }

    // ── POST /cobros/rutas/{ruta_id}/orden ────────────────────────────────────

    #[OA\Post(
        path: '/cobros/rutas/{ruta_id}/orden',
        summary: 'Guardar el orden de visita de los clientes de una ruta',
        description: 'El cobrador reordena su ruta en la app y ese orden queda guardado en el servidor '
            .'(columna clientes.orden, la misma que usa el panel administrativo).',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'ruta_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), example: [3, 1, 7, 2]),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Orden guardado'),
            new OA\Response(response: 403, description: 'Ruta no encontrada o no te pertenece'),
            new OA\Response(response: 422, description: 'Alguno de los IDs no pertenece a esta ruta'),
        ],
    )]
    public function actualizarOrden(Request $request, int $rutaId): JsonResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $ruta = $cobrador->rutasCobro()->where('id', $rutaId)->first();
        if (! $ruta) {
            return response()->json(['mensaje' => 'Ruta no encontrada o no te pertenece.'], 403);
        }

        // Se depuran duplicados (se conserva la primera posición en que aparece
        // cada cliente) para no dejar huecos ni clientes con el mismo orden.
        $idsUnicos = array_values(array_unique($data['ids']));

        $idsValidos = $ruta->clientes()->pluck('id');
        $idsInvalidos = array_diff($idsUnicos, $idsValidos->all());
        if (! empty($idsInvalidos)) {
            return response()->json(['mensaje' => 'Alguno de los clientes no pertenece a esta ruta.'], 422);
        }

        DB::transaction(function () use ($idsUnicos) {
            foreach ($idsUnicos as $posicion => $clienteId) {
                Cliente::where('id', $clienteId)->update(['orden' => $posicion + 1]);
            }
        });

        return response()->json(['mensaje' => 'Orden guardado.']);
    }

    // ── GET /cobros/clientes/buscar ───────────────────────────────────────────

    #[OA\Get(
        path: '/cobros/clientes/buscar',
        summary: 'Buscar un cliente por código, entre todos los clientes del cobrador',
        description: 'Busca por código (código anterior) entre TODOS los clientes de las rutas del cobrador, '
            .'sin importar si la ruta es la de hoy o de otro día. Útil cuando un cliente paga fuera de su '
            .'día habitual de visita.',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'codigo', in: 'query', required: true, schema: new OA\Schema(type: 'string'), example: '7304'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Clientes encontrados'),
            new OA\Response(response: 403, description: 'No se encontró perfil de cobrador'),
            new OA\Response(response: 422, description: 'Falta el parámetro codigo'),
        ],
    )]
    public function buscarCliente(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:50',
        ]);

        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $rutasIds = $cobrador->rutasCobro()->pluck('id');

        $clientes = Cliente::whereIn('ruta_cobro_id', $rutasIds)
            ->where('activo', true)
            ->where('codigo_anterior', 'like', '%'.$data['codigo'].'%')
            ->with('rutaCobro:id,nombre,dia_semana')
            ->orderBy('codigo_anterior')
            ->get()
            ->map(fn (Cliente $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre_completo,
                'codigo_anterior' => $c->codigo_anterior,
                'telefono' => $c->telefono_normal,
                'direccion' => $c->direccion,
                'saldo_total' => (float) $c->saldo,
                'ruta' => [
                    'id' => $c->ruta_cobro_id,
                    'nombre' => $c->rutaCobro?->nombre,
                    'dia_semana' => $c->rutaCobro?->dia_semana,
                ],
            ]);

        return response()->json([
            'total' => $clientes->count(),
            'clientes' => $clientes,
        ]);
    }

    // ── GET /cobros/clientes/{id} ─────────────────────────────────────────────

    #[OA\Get(
        path: '/cobros/clientes/{id}',
        summary: 'Detalle del cliente con historial de cobros',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del cliente'),
            new OA\Response(response: 403, description: 'Cliente no pertenece a tus rutas'),
        ],
    )]
    public function detalleCliente(Request $request, int $id): JsonResponse
    {
        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $cliente = $this->clienteDeRuta($id, $cobrador);

        if (! $cliente) {
            return response()->json(['mensaje' => 'Este cliente no pertenece a tus rutas.'], 403);
        }

        // Gestiones agrupadas por venta
        $gestiones = GestionCobro::where('cliente_id', $id)
            ->with([
                'venta:id,numero_venta,total,fecha_venta,estado,monto_pagado,saldo_pendiente',
                'venta.detalles.producto:id,nombre',
            ])
            ->orderBy('venta_id')
            ->orderBy('numero_cuota')
            ->get();

        $ventas = $gestiones->groupBy('venta_id')->map(function ($cuotas) {
            $venta    = $cuotas->first()->venta;
            $vencidas = $cuotas->filter(fn ($g) => $g->fecha_vencimiento->isPast() && $g->estado !== 'cobrado');

            return [
                'id'              => $venta->id,
                'numero_venta'    => $venta->numero_venta,
                // Nombres de los productos de la venta unidos por coma (una venta
                // puede tener más de una línea) — string único, no array, para
                // que la app lo lea directo como venta.producto.
                'producto'        => $venta->detalles->pluck('producto.nombre')->filter()->implode(', '),
                'fecha_venta'     => $venta->fecha_venta instanceof \Carbon\Carbon
                    ? $venta->fecha_venta->format('d/m/Y')
                    : $venta->fecha_venta,
                'total'           => (float) $venta->total,
                'monto_pagado'    => (float) $venta->monto_pagado,
                'saldo_pendiente' => (float) $venta->saldo_pendiente,
                'estado'          => $venta->estado,
                'resumen' => [
                    'total_cuotas'   => $cuotas->count(),
                    'cobradas'       => $cuotas->where('estado', 'cobrado')->count(),
                    'pendientes'     => $cuotas->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])->count(),
                    'vencidas'       => $vencidas->count(),
                    'monto_vencido'  => round($vencidas->sum(fn ($g) => $g->monto_cuota - $g->monto_pagado), 2),
                ],
                'cuotas' => $cuotas->map(fn ($g) => [
                    'id'               => $g->id,
                    'cuota'            => "{$g->numero_cuota}/{$g->total_cuotas}",
                    'monto_cuota'      => (float) $g->monto_cuota,
                    'monto_pagado'     => (float) $g->monto_pagado,
                    'saldo_pendiente'  => round($g->monto_cuota - $g->monto_pagado, 2),
                    'fecha_vencimiento'=> $g->fecha_vencimiento->format('d/m/Y'),
                    'estado'           => $g->estado,
                    'vencida'          => $g->fecha_vencimiento->isPast() && $g->estado !== 'cobrado',
                ])->values(),
            ];
        })->values();

        $totalPendientes = $gestiones->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])->count();
        $totalVencidas   = $gestiones->filter(fn ($g) => $g->fecha_vencimiento->isPast() && $g->estado !== 'cobrado')->count();

        return response()->json([
            'cliente' => [
                'id'             => $cliente->id,
                'nombre'         => $cliente->nombre_completo,
                'dui'            => $cliente->dui,
                'codigo_anterior'=> $cliente->codigo_anterior,
                'telefono'       => $cliente->telefono_normal,
                'whatsapp'       => $cliente->telefono_whatsapp,
                'direccion'      => $cliente->direccion,
                'saldo_total'    => (float) $cliente->saldo,
                'ruta'           => $cliente->rutaCobro?->nombre,
            ],
            'resumen' => [
                'total_ventas'   => $ventas->count(),
                'cuotas_pendientes' => $totalPendientes,
                'cuotas_vencidas'   => $totalVencidas,
            ],
            'ventas' => $ventas,
        ]);
    }

    // ── GET /cobros/clientes/{id}/gestiones-pendientes ────────────────────────

    #[OA\Get(
        path: '/cobros/clientes/{id}/gestiones-pendientes',
        summary: 'Cuotas pendientes o parciales de un cliente',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Gestiones pendientes'),
            new OA\Response(response: 403, description: 'Cliente no pertenece a tus rutas'),
        ],
    )]
    public function gestionesPendientes(Request $request, int $id): JsonResponse
    {
        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $cliente = $this->clienteDeRuta($id, $cobrador);

        if (! $cliente) {
            return response()->json(['mensaje' => 'Este cliente no pertenece a tus rutas.'], 403);
        }

        $gestiones = GestionCobro::where('cliente_id', $id)
            ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
            ->with('venta:id,numero_venta,total')
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'venta' => $g->venta?->numero_venta,
                'cuota' => "{$g->numero_cuota}/{$g->total_cuotas}",
                'monto_cuota' => $g->monto_cuota,
                'monto_pagado' => $g->monto_pagado,
                'saldo_pendiente' => round($g->monto_cuota - $g->monto_pagado, 2),
                'fecha_vencimiento' => $g->fecha_vencimiento->format('d/m/Y'),
                'estado' => $g->estado,
                'vencida' => $g->fecha_vencimiento->isPast(),
            ]);

        return response()->json([
            'cliente_id' => $id,
            'total_pendiente' => $gestiones->sum('saldo_pendiente'),
            'gestiones' => $gestiones,
        ]);
    }

    // ── POST /cobros/clientes/{id}/pagar ─────────────────────────────────────

    #[OA\Post(
        path: '/cobros/clientes/{id}/pagar',
        summary: 'Registrar pago directo a un cliente (opcionalmente a una venta específica)',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['monto', 'metodo_pago', 'venta_id'],
                properties: [
                    new OA\Property(property: 'monto', type: 'number', format: 'float', example: 14.00),
                    new OA\Property(property: 'metodo_pago', type: 'string', enum: ['efectivo', 'transferencia', 'cheque', 'deposito']),
                    new OA\Property(property: 'venta_id', type: 'integer', example: 20, description: 'ID de la venta a la que se aplica el pago'),
                    new OA\Property(property: 'referencia', type: 'string', nullable: true),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pago registrado'),
            new OA\Response(response: 404, description: 'No hay cuotas pendientes'),
            new OA\Response(response: 403, description: 'Cliente no pertenece a tus rutas o venta no pertenece al cliente'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function pagarCliente(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'monto'         => 'required|numeric|min:0.01',
            'metodo_pago'   => 'required|in:efectivo,transferencia,cheque,deposito',
            'venta_id'      => 'required|integer|exists:ventas,id',
            'referencia'    => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $cliente = $this->clienteDeRuta($id, $cobrador);
        if (! $cliente) {
            return response()->json(['mensaje' => 'Este cliente no pertenece a tus rutas.'], 403);
        }

        $monto = (float) $data['monto'];
        $ventaId = (int) $data['venta_id'];

        // Validar que la venta pertenece al cliente
        $existeVenta = \App\Models\Venta::where('id', $ventaId)->where('cliente_id', $id)->exists();
        if (! $existeVenta) {
            return response()->json(['mensaje' => 'Esta venta no pertenece al cliente.'], 403);
        }

        $result = DB::transaction(function () use ($id, $monto, $data, $ventaId) {
            // Se bloquea la fila de la venta y se revalida el saldo DENTRO de la
            // transacción (no antes), para que dos pagos casi simultáneos sobre
            // la misma venta no puedan ambos pasar la validación con el mismo
            // saldo desactualizado y generar un sobre-cobro.
            $venta = \App\Models\Venta::where('id', $ventaId)
                ->where('cliente_id', $id)
                ->lockForUpdate()
                ->first();

            $saldoVenta = (float) $venta->saldo_pendiente;
            if ($monto > $saldoVenta) {
                throw ValidationException::withMessages([
                    'monto' => "El monto \${$monto} supera el saldo pendiente de esta venta (\${$saldoVenta}).",
                ]);
            }

            $gestiones = GestionCobro::where('cliente_id', $id)
                ->where('venta_id', $ventaId)
                ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                ->orderBy('fecha_vencimiento')
                ->orderBy('numero_cuota')
                ->lockForUpdate()
                ->with('venta')
                ->get();

            $restante      = $monto;
            $cuotasPagadas = [];

            foreach ($gestiones as $gestion) {
                if ($restante <= 0) break;

                $saldoCuota = round($gestion->monto_cuota - $gestion->monto_pagado, 2);
                $aplicar    = min($restante, $saldoCuota);

                PagoVenta::create([
                    'venta_id'      => $gestion->venta_id,
                    'cliente_id'    => $id,
                    'monto'         => $aplicar,
                    'fecha_pago'    => today(),
                    'metodo_pago'   => $data['metodo_pago'],
                    'referencia'    => $data['referencia'] ?? null,
                    'observaciones' => $data['observaciones'] ?? null,
                    'user_id'       => auth()->id(),
                ]);

                $gestion->increment('monto_pagado', $aplicar);
                $gestion->refresh();
                $estadoCuota = $gestion->monto_pagado >= $gestion->monto_cuota ? 'cobrado' : 'parcialmente_cobrado';
                $gestion->update(['estado' => $estadoCuota]);

                // La venta se actualiza automáticamente en PagoVenta::boot
                $venta = $gestion->venta;
                $venta->refresh();

                $cuotasPagadas[] = [
                    'id'              => $gestion->id,
                    'cuota'           => "{$gestion->numero_cuota}/{$gestion->total_cuotas}",
                    'monto_aplicado'  => round($aplicar, 2),
                    'saldo_pendiente' => round($gestion->monto_cuota - $gestion->monto_pagado, 2),
                    'estado'          => $estadoCuota,
                ];

                $restante = round($restante - $aplicar, 2);
            }

            $proxima = GestionCobro::where('cliente_id', $id)
                ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                ->orderBy('fecha_vencimiento')
                ->orderBy('numero_cuota')
                ->first();

            return compact('cuotasPagadas', 'proxima');
        });

        // Nombre(s) del producto de la venta pagada, para el ticket impreso —
        // una venta puede tener más de una línea, se unen por coma en un solo
        // string (mismo criterio que GET /cobros/clientes/{id}).
        $producto = \App\Models\DetalleVenta::where('venta_id', $ventaId)
            ->with('producto:id,nombre')
            ->get()
            ->pluck('producto.nombre')
            ->filter()
            ->implode(', ');

        return response()->json([
            'mensaje'       => 'Pago registrado y distribuido en ' . count($result['cuotasPagadas']) . ' cuota(s).',
            'cliente'       => [
                'id'              => $cliente->id,
                'codigo_anterior' => $cliente->codigo_anterior,
            ],
            'producto'      => $producto,
            'monto_total'   => $monto,
            'cuotas_pagadas' => $result['cuotasPagadas'],
            'proxima_cuota' => $result['proxima'] ? [
                'id'               => $result['proxima']->id,
                'cuota'            => "{$result['proxima']->numero_cuota}/{$result['proxima']->total_cuotas}",
                'monto_cuota'      => $result['proxima']->monto_cuota,
                'saldo_pendiente'  => round($result['proxima']->monto_cuota - $result['proxima']->monto_pagado, 2),
                'fecha_vencimiento'=> $result['proxima']->fecha_vencimiento->format('d/m/Y'),
                'estado'           => $result['proxima']->estado,
            ] : null,
        ]);
    }

    // ── POST /cobros/gestiones/{id}/pagar ─────────────────────────────────────

    #[OA\Post(
        path: '/cobros/gestiones/{id}/pagar',
        summary: 'Registrar pago de una cuota',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['monto', 'metodo_pago'],
                properties: [
                    new OA\Property(property: 'monto', type: 'number', format: 'float', example: 14.00),
                    new OA\Property(property: 'metodo_pago', type: 'string', enum: ['efectivo', 'transferencia', 'cheque', 'deposito']),
                    new OA\Property(property: 'referencia', type: 'string', nullable: true),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pago registrado'),
            new OA\Response(response: 403, description: 'Gestión no pertenece a tus rutas'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function pagar(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:efectivo,transferencia,cheque,deposito',
            'referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $gestion = GestionCobro::with('venta')->findOrFail($id);

        $cliente = $this->clienteDeRuta($gestion->cliente_id, $cobrador);
        if (! $cliente) {
            return response()->json(['mensaje' => 'Esta gestión no pertenece a tus rutas.'], 403);
        }

        $monto = (float) $data['monto'];

        $result = DB::transaction(function () use ($id, $monto, $data) {
            // Se vuelve a leer la gestión con lock dentro de la transacción (no la
            // ya cargada antes) para revalidar los saldos con datos frescos y
            // evitar que dos pagos casi simultáneos sobre-cobren la misma cuota.
            $gestion = GestionCobro::where('id', $id)->lockForUpdate()->with('venta')->first();

            if ($gestion->numero_cuota > 1) {
                $cuotaAnterior = GestionCobro::where('venta_id', $gestion->venta_id)
                    ->where('numero_cuota', $gestion->numero_cuota - 1)
                    ->lockForUpdate()
                    ->first();

                if ($cuotaAnterior && ($cuotaAnterior->monto_cuota - $cuotaAnterior->monto_pagado) > 0) {
                    $pendiente = round($cuotaAnterior->monto_cuota - $cuotaAnterior->monto_pagado, 2);
                    throw ValidationException::withMessages([
                        'monto' => "La cuota anterior tiene \${$pendiente} pendiente. Págala primero.",
                    ]);
                }
            }

            $saldoGestion = round($gestion->monto_cuota - $gestion->monto_pagado, 2);
            if ($monto > $saldoGestion) {
                throw ValidationException::withMessages([
                    'monto' => "El monto no puede exceder \${$saldoGestion} pendiente de esta cuota.",
                ]);
            }

            // Registrar pago
            PagoVenta::create([
                'venta_id' => $gestion->venta_id,
                'cliente_id' => $gestion->cliente_id,
                'monto' => $monto,
                'fecha_pago' => today(),
                'metodo_pago' => $data['metodo_pago'],
                'referencia' => $data['referencia'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'user_id' => auth()->id(),
            ]);

            // Actualizar gestión atómicamente
            $gestion->increment('monto_pagado', $monto);
            $gestion->refresh();
            $estado = $gestion->monto_pagado >= $gestion->monto_cuota ? 'cobrado' : 'parcialmente_cobrado';
            $gestion->update(['estado' => $estado]);

            // La venta se actualiza automáticamente en PagoVenta::boot
            $venta = $gestion->venta;
            $venta->refresh();

            return [
                'estado' => $estado,
                'gestion' => $gestion,
                'venta_saldo' => $venta->saldo_pendiente,
            ];
        });

        return response()->json([
            'mensaje' => $result['estado'] === 'cobrado' ? 'Cuota pagada completamente.' : 'Pago parcial registrado.',
            'gestion' => [
                'id' => $result['gestion']->id,
                'cuota' => "{$result['gestion']->numero_cuota}/{$result['gestion']->total_cuotas}",
                'monto_cuota' => $result['gestion']->monto_cuota,
                'monto_pagado' => $result['gestion']->monto_pagado,
                'saldo_pendiente' => round($result['gestion']->monto_cuota - $result['gestion']->monto_pagado, 2),
                'estado' => $result['estado'],
            ],
            'venta_saldo' => $result['venta_saldo'],
        ]);
    }

    #[OA\Get(
        path: '/cobros/resumen-dia',
        summary: 'Resumen de cobros del día por cobrador',
        description: 'Muestra cuánto cobró cada cobrador en una fecha (default: hoy). Incluye desglose por método de pago, número de pagos realizados y clientes visitados.',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(
                name: 'fecha',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-15'),
                description: 'Fecha a consultar. Default: hoy.'
            ),
            new OA\Parameter(
                name: 'cobrador_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'Filtrar por un cobrador específico.'
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Resumen por cobrador'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function resumenDia(Request $request): JsonResponse
    {
        $fecha = $request->filled('fecha') ? $request->date('fecha') : today();

        // Este endpoint es de autoservicio: cada quien ve únicamente su propio
        // resumen. Se ignora cualquier cobrador_id que mande el cliente — antes
        // se podía consultar el resumen de CUALQUIER cobrador de la empresa.
        $cobrador = $request->user()->cobrador;

        if (! $cobrador) {
            return response()->json([
                'mensaje' => 'No se encontró perfil de cobrador.',
            ], 403);
        }

        $cobradores = Cobrador::with('user')
            ->where('id', $cobrador->id)
            ->where('activo', true)
            ->where('excluir_reportes', false)
            ->get();

        $resumen = $cobradores->map(function ($cobrador) use ($fecha) {
            // Pagos del día registrados por el user de este cobrador
            $pagos = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)
                ->selectRaw('
                    COUNT(DISTINCT cliente_id)  AS total_pagos,
                    COUNT(DISTINCT cliente_id)  AS clientes_visitados,
                    SUM(monto)                  AS total_cobrado
                ')
                ->first();

            // Desglose por método de pago
            $porMetodo = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)
                ->selectRaw('metodo_pago, COUNT(*) AS cantidad, SUM(monto) AS monto')
                ->groupBy('metodo_pago')
                ->get()
                ->mapWithKeys(fn ($row) => [
                    $row->metodo_pago => [
                        'cantidad' => (int) $row->cantidad,
                        'monto'    => round((float) $row->monto, 2),
                    ],
                ]);

            // Detalle de cada pago
            $detalle = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)
                ->with('cliente:id,nombre,apellido', 'venta:id,numero_venta')
                ->orderBy('created_at')
                ->get()
                ->map(fn ($p) => [
                    'cliente'      => $p->cliente?->nombre_completo,
                    'venta'        => $p->venta?->numero_venta,
                    'monto'        => (float) $p->monto,
                    'metodo_pago'  => $p->metodo_pago,
                    'referencia'   => $p->referencia,
                    'hora'         => $p->created_at->format('H:i'),
                ]);

            // Visitas sin cobro del día
            $visitasSinCobro = VisitaCobro::where('user_id', $cobrador->user_id)
                ->whereDate('created_at', $fecha)
                ->with('cliente:id,nombre,apellido')
                ->orderBy('created_at')
                ->get()
                ->map(fn ($v) => [
                    'cliente'       => $v->cliente?->nombre_completo,
                    'resultado'     => $v->resultado,
                    'promesa_fecha' => $v->promesa_fecha?->format('d/m/Y'),
                    'observaciones' => $v->observaciones,
                    'foto_hogar_url'=> $v->foto_hogar ? \Illuminate\Support\Facades\Storage::url($v->foto_hogar) : null,
                    'hora'          => $v->created_at->format('H:i'),
                ]);

            return [
                'cobrador_id'        => $cobrador->id,
                'cobrador'           => trim(($cobrador->nombre ?? '') . ' ' . ($cobrador->apellido ?? '')),
                'total_cobrado'      => round((float) ($pagos->total_cobrado ?? 0), 2),
                'total_pagos'        => (int) ($pagos->total_pagos ?? 0),
                'clientes_visitados' => (int) ($pagos->clientes_visitados ?? 0),
                'visitas_sin_cobro'  => $visitasSinCobro->count(),
                'por_metodo'         => $porMetodo,
                'detalle'            => $detalle,
                'sin_cobro'          => $visitasSinCobro,
            ];
        });

        return response()->json([
            'fecha'      => $fecha->toDateString(),
            'cobradores' => $resumen,
            'totales'    => [
                'total_cobrado'      => round($resumen->sum('total_cobrado'), 2),
                'total_pagos'        => $resumen->sum('total_pagos'),
                'clientes_visitados' => $resumen->sum('clientes_visitados'),
                'visitas_sin_cobro'  => $resumen->sum('visitas_sin_cobro'),
            ],
        ]);
    }

    // ── GET /cobros/historial ────────────────────────────────────────────────

    #[OA\Get(
        path: '/cobros/historial',
        summary: 'Historial de cobros, visitas y gastos de un día del cobrador autenticado',
        description: 'Lista, del más reciente al más antiguo, cada pago, cada visita sin pago y cada vale (gasto) que el cobrador autenticado registró en la fecha indicada (campo "tipo": "pago", "visita" o "gasto"). '
            .'Sin el parámetro "fecha" devuelve el día de hoy. Solo se permite consultar días del mes en curso (hasta hoy), para que el selector de fecha de la app muestre un mes a la vez.',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'fecha', in: 'query', required: false, description: 'Día a consultar (YYYY-MM-DD). Por defecto, hoy.', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Historial del día'),
            new OA\Response(response: 403, description: 'Sin perfil de cobrador'),
            new OA\Response(response: 422, description: 'La fecha está fuera del mes en curso', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function historial(Request $request): JsonResponse
    {
        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $data = $request->validate([
            'fecha' => 'nullable|date',
        ]);

        $fecha = isset($data['fecha']) ? \Illuminate\Support\Carbon::parse($data['fecha']) : today();

        if ($fecha->gt(today()) || $fecha->lt(today()->startOfMonth())) {
            return response()->json(['mensaje' => 'Solo puedes consultar días del mes en curso, hasta hoy.'], 422);
        }

        $pagos = PagoVenta::where('user_id', $cobrador->user_id)
            ->whereDate('fecha_pago', $fecha)
            ->with(['cliente:id,nombre,apellido,codigo_anterior,telefono_whatsapp', 'venta:id,numero_venta', 'venta.detalles.producto:id,nombre,codigo'])
            ->get()
            ->map(fn ($p) => [
                'tipo'          => 'pago',
                'id'            => $p->id,
                'cliente'       => $p->cliente ? [
                    'id'              => $p->cliente->id,
                    'nombre'          => $p->cliente->nombre_completo,
                    'codigo_anterior' => $p->cliente->codigo_anterior,
                    'whatsapp'        => $p->cliente->telefono_whatsapp,
                ] : null,
                'numero_venta'  => $p->venta?->numero_venta,
                'productos'     => $p->venta?->detalles->map(fn ($d) => [
                    'nombre'   => $d->producto?->nombre,
                    'codigo'   => $d->producto?->codigo,
                    'cantidad' => (int) $d->cantidad,
                ])->values() ?? [],
                'monto'         => (float) $p->monto,
                'metodo_pago'   => $p->metodo_pago,
                'referencia'    => $p->referencia,
                'hora'          => $p->created_at->format('H:i'),
                '_ts'           => $p->created_at,
            ]);

        $visitas = VisitaCobro::where('user_id', $cobrador->user_id)
            ->whereDate('fecha_visita', $fecha)
            ->with('cliente:id,nombre,apellido,codigo_anterior,telefono_whatsapp')
            ->get()
            ->map(fn ($v) => [
                'tipo'           => 'visita',
                'id'             => $v->id,
                'cliente'        => $v->cliente ? [
                    'id'              => $v->cliente->id,
                    'nombre'          => $v->cliente->nombre_completo,
                    'codigo_anterior' => $v->cliente->codigo_anterior,
                    'whatsapp'        => $v->cliente->telefono_whatsapp,
                ] : null,
                'resultado'      => $v->resultado,
                'promesa_fecha'  => $v->promesa_fecha?->toDateString(),
                'observaciones'  => $v->observaciones,
                'foto_hogar_url' => $v->foto_hogar ? Storage::url($v->foto_hogar) : null,
                'hora'           => $v->created_at->format('H:i'),
                '_ts'            => $v->created_at,
            ]);

        // Gastos (vales) del propio cobrador ese día — consumo y vehículo, en
        // cualquier estado (pendiente/aprobado/rechazado), para que quede
        // constancia en su historial de lo que registró, no solo lo cobrado.
        $gastos = Vale::where('user_id', $cobrador->user_id)
            ->whereDate('fecha_gasto', $fecha)
            ->with('vehiculo:id,placa')
            ->get()
            ->map(fn (Vale $g) => [
                'tipo'                   => 'gasto',
                'id'                     => $g->id,
                'tipo_gasto'             => $g->tipo,
                'vehiculo'               => $g->vehiculo?->placa,
                'categoria_vehiculo'     => $g->categoria_vehiculo,
                'monto'                  => (float) $g->monto,
                'comprobante_url'        => $g->comprobante_url,
                'descripcion'            => $g->descripcion,
                'estado'                 => $g->estado,
                'descuenta_cobro_diario' => $g->descuenta_cobro_diario,
                'hora'                   => $g->created_at->format('H:i'),
                '_ts'                    => $g->created_at,
            ]);

        $items = $pagos->concat($visitas)
            ->concat($gastos)
            ->sortByDesc('_ts')
            ->values()
            ->map(fn ($item) => \Illuminate\Support\Arr::except($item, ['_ts']));

        return response()->json([
            'fecha'         => $fecha->toDateString(),
            'total_cobrado' => round((float) $pagos->sum('monto'), 2),
            'total_gastado' => round((float) $gastos->where('descuenta_cobro_diario', true)->sum('monto'), 2),
            'total_pagos'   => $pagos->count(),
            'total_visitas' => $visitas->count(),
            'total_gastos'  => $gastos->count(),
            'items'         => $items,
        ]);
    }

    // ── POST /cobros/clientes/{id}/visita ──────────────────────────────────────

    #[OA\Post(
        path: '/cobros/clientes/{id}/visita',
        summary: 'Registrar visita sin cobro a un cliente',
        description: 'Permite al cobrador registrar que visitó al cliente aunque no haya recibido pago. Se puede adjuntar foto del hogar como comprobante. '
            .'Si resultado=abono_previo (el cliente ya pagó su mensualidad por otro medio), no se requiere foto ni coordenadas GPS y no se genera ningún cobro nuevo, solo queda constancia de la visita.',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID del cliente'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['resultado'],
                    properties: [
                        new OA\Property(property: 'resultado', type: 'string', enum: ['sin_pago', 'promesa_pago', 'no_encontrado', 'rechazo', 'abono_previo'], example: 'sin_pago'),
                        new OA\Property(property: 'gestion_cobro_id', type: 'integer', nullable: true, description: 'Cuota específica relacionada (opcional)'),
                        new OA\Property(property: 'observaciones', type: 'string', nullable: true, example: 'El cliente dijo que paga el viernes'),
                        new OA\Property(property: 'promesa_fecha', type: 'string', format: 'date', nullable: true, description: 'Requerido si resultado=promesa_pago'),
                        new OA\Property(property: 'foto_hogar', type: 'string', format: 'binary', nullable: true, description: 'Foto del hogar como comprobante (jpg/png, max 5MB)'),
                        new OA\Property(property: 'latitud', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'longitud', type: 'number', format: 'float', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Visita registrada'),
            new OA\Response(response: 403, description: 'Cliente no pertenece a tus rutas'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function registrarVisita(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'resultado'       => 'required|in:sin_pago,promesa_pago,no_encontrado,rechazo,abono_previo',
            'gestion_cobro_id'=> 'nullable|integer|exists:gestion_cobros,id',
            'observaciones'   => 'nullable|string|max:500',
            'promesa_fecha'   => 'required_if:resultado,promesa_pago|nullable|date|after:today',
            'foto_hogar'      => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'latitud'         => 'nullable|numeric|between:-90,90',
            'longitud'        => 'nullable|numeric|between:-180,180',
        ]);

        $cobrador = $this->cobrador($request);
        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $cliente = $this->clienteDeRuta($id, $cobrador);
        if (! $cliente) {
            return response()->json(['mensaje' => 'Este cliente no pertenece a tus rutas.'], 403);
        }

        // abono_previo: el cliente ya pagó su mensualidad por otro medio. No genera
        // cobro nuevo, solo deja constancia de la visita — no requiere foto ni GPS.
        $fotoPath = null;
        $latitud = null;
        $longitud = null;

        if ($request->resultado !== 'abono_previo') {
            if ($request->hasFile('foto_hogar')) {
                $fotoPath = $request->file('foto_hogar')
                    ->store("visitas/{$id}", 'public');
            }

            $latitud = $data['latitud'] ?? null;
            $longitud = $data['longitud'] ?? null;
        }

        // Protección contra doble-tap / reintento de red: si ya se registró una
        // visita idéntica hace unos segundos, no se duplica.
        $duplicada = VisitaCobro::where('cliente_id', $id)
            ->where('user_id', $request->user()->id)
            ->where('resultado', $data['resultado'])
            ->where('created_at', '>=', now()->subSeconds(10))
            ->exists();

        if ($duplicada) {
            return response()->json(['mensaje' => 'Ya se registró esta visita hace unos segundos.'], 409);
        }

        $visita = VisitaCobro::create([
            'cliente_id'      => $id,
            'user_id'         => $request->user()->id,
            'gestion_cobro_id'=> $data['gestion_cobro_id'] ?? null,
            'fecha_visita'    => now(),
            'resultado'       => $data['resultado'],
            'promesa_fecha'   => $data['promesa_fecha'] ?? null,
            'foto_hogar'      => $fotoPath,
            'observaciones'   => $data['observaciones'] ?? null,
            'latitud'         => $latitud,
            'longitud'        => $longitud,
        ]);

        return response()->json([
            'mensaje' => 'Visita registrada.',
            'visita'  => [
                'id'            => $visita->id,
                'cliente_id'    => $visita->cliente_id,
                'resultado'     => $visita->resultado,
                'promesa_fecha' => $visita->promesa_fecha?->format('d/m/Y'),
                'fecha_visita'  => $visita->fecha_visita->format('d/m/Y H:i'),
                'foto_hogar_url'=> $fotoPath ? Storage::url($fotoPath) : null,
                'observaciones' => $visita->observaciones,
            ],
        ], 201);
    }
}
