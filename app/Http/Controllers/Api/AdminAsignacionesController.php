<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsignacionDiaria;
use App\Models\DetalleAsignacion;
use App\Models\Producto;
use App\Models\Vendedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AdminAsignacionesController extends Controller
{
    private function autorizar(Request $request): ?JsonResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            return response()->json(['mensaje' => 'No autorizado.'], 403);
        }

        return null;
    }

    #[OA\Get(
        path: '/admin/vendedores',
        summary: 'Vendedores activos, para el selector de asignaciones (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
    )]
    public function vendedores(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $vendedores = Vendedor::where('activo', true)
            ->whereNotNull('user_id')
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'apellido', 'sucursal_id']);

        return response()->json($vendedores->map(fn ($v) => [
            'id'          => $v->id,
            'codigo'      => $v->codigo,
            'nombre'      => trim($v->nombre . ' ' . $v->apellido),
            'sucursal_id' => $v->sucursal_id,
        ]));
    }

    #[OA\Get(
        path: '/admin/productos-catalogo',
        summary: 'Catálogo de productos activos, para armar una asignación (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'buscar', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
    )]
    public function productos(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $buscar = trim((string) $request->query('buscar', ''));

        $productos = Producto::where('activo', true)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('codigo', 'like', "%{$buscar}%")))
            ->orderBy('nombre')
            ->limit(60)
            ->get(['id', 'nombre', 'codigo', 'stock', 'precio_venta']);

        return response()->json($productos);
    }

    #[OA\Get(
        path: '/admin/asignaciones',
        summary: 'Asignaciones diarias de una fecha, de todos los vendedores (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $fecha = $request->query('fecha') ?: today()->toDateString();

        $asignaciones = AsignacionDiaria::with(['vendedor:id,nombre,apellido,codigo', 'sucursal:id,nombre', 'detalles.producto:id,nombre,codigo'])
            ->whereDate('fecha', $fecha)
            ->latest('id')
            ->get();

        return response()->json($asignaciones->map(fn (AsignacionDiaria $a) => [
            'id'             => $a->id,
            'fecha'          => $a->fecha->toDateString(),
            'estado'         => $a->estado,
            'vendedor'       => $a->vendedor ? trim($a->vendedor->nombre . ' ' . $a->vendedor->apellido) : '—',
            'sucursal'       => $a->sucursal?->nombre,
            'total_vendido'  => (float) $a->total_vendido,
            'observaciones'  => $a->observaciones,
            'productos'      => $a->detalles->map(fn (DetalleAsignacion $d) => [
                'nombre'            => $d->producto?->nombre,
                'codigo'            => $d->producto?->codigo,
                'cantidad_asignada' => $d->cantidad_asignada,
                'cantidad_vendida'  => $d->cantidad_vendida,
            ])->values(),
        ]));
    }

    #[OA\Post(
        path: '/admin/asignaciones',
        summary: 'Crear una asignación diaria para un vendedor (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['vendedor_id', 'sucursal_id', 'detalles'],
                properties: [
                    new OA\Property(property: 'vendedor_id', type: 'integer'),
                    new OA\Property(property: 'sucursal_id', type: 'integer'),
                    new OA\Property(property: 'fecha', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'detalles',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'producto_id', type: 'integer'),
                            new OA\Property(property: 'cantidad_asignada', type: 'number'),
                        ]),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Asignación creada'),
            new OA\Response(response: 422, description: 'Validación fallida o ya existe una asignación activa para ese vendedor y fecha'),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $data = $request->validate([
            'vendedor_id'                     => 'required|exists:vendedores,id',
            'sucursal_id'                      => 'required|exists:sucursales,id',
            'fecha'                            => 'nullable|date',
            'observaciones'                    => 'nullable|string|max:500',
            'detalles'                         => 'required|array|min:1',
            'detalles.*.producto_id'           => 'required|exists:productos,id',
            'detalles.*.cantidad_asignada'     => 'required|numeric|min:1',
        ]);

        $fecha = $data['fecha'] ?? today()->toDateString();

        // Misma regla que el panel web: no se puede crear una segunda
        // asignación activa para el mismo vendedor el mismo día — primero
        // hay que liquidar la anterior.
        $yaActiva = AsignacionDiaria::where('vendedor_id', $data['vendedor_id'])
            ->whereDate('fecha', $fecha)
            ->where('estado', 'activa')
            ->exists();

        if ($yaActiva) {
            return response()->json([
                'mensaje' => 'Este vendedor ya tiene una asignación activa para esa fecha. Liquídala antes de crear otra.',
            ], 422);
        }

        $asignacion = DB::transaction(function () use ($data, $fecha) {
            $productos = Producto::whereIn('id', collect($data['detalles'])->pluck('producto_id'))
                ->get()->keyBy('id');

            $asignacion = AsignacionDiaria::create([
                'vendedor_id'   => $data['vendedor_id'],
                'sucursal_id'   => $data['sucursal_id'],
                'fecha'         => $fecha,
                'estado'        => 'activa',
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            foreach ($data['detalles'] as $item) {
                $producto = $productos->get($item['producto_id']);

                DetalleAsignacion::create([
                    'asignacion_id'     => $asignacion->id,
                    'producto_id'       => $item['producto_id'],
                    'cantidad_asignada' => $item['cantidad_asignada'],
                    'precio_venta'      => $producto?->precio_venta ?? 0,
                ]);
            }

            return $asignacion->load('detalles.producto:id,nombre,codigo', 'vendedor:id,nombre,apellido');
        });

        return response()->json([
            'mensaje'    => 'Asignación creada.',
            'asignacion' => [
                'id'       => $asignacion->id,
                'fecha'    => $asignacion->fecha->toDateString(),
                'estado'   => $asignacion->estado,
                'vendedor' => trim($asignacion->vendedor->nombre . ' ' . $asignacion->vendedor->apellido),
                'productos'=> $asignacion->detalles->map(fn ($d) => [
                    'nombre'            => $d->producto?->nombre,
                    'cantidad_asignada' => $d->cantidad_asignada,
                ])->values(),
            ],
        ], 201);
    }

    #[OA\Post(
        path: '/admin/asignaciones/{id}/liquidar',
        summary: 'Liquidar (cerrar) la asignación diaria de un vendedor (solo super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin'],
        responses: [
            new OA\Response(response: 200, description: 'Jornada liquidada'),
            new OA\Response(response: 422, description: 'Ya estaba liquidada'),
        ],
    )]
    public function liquidar(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->autorizar($request)) {
            return $resp;
        }

        $data = $request->validate([
            'observaciones' => 'nullable|string|max:500',
        ]);

        $asignacion = DB::transaction(function () use ($id, $data) {
            $asignacion = AsignacionDiaria::lockForUpdate()->findOrFail($id);

            if (! $asignacion->estaActiva()) {
                return null;
            }

            $asignacion->liquidar($data['observaciones'] ?? null);

            return $asignacion;
        });

        if (! $asignacion) {
            return response()->json(['mensaje' => 'Esta asignación ya fue liquidada.'], 422);
        }

        return response()->json(['mensaje' => 'Jornada liquidada correctamente.']);
    }
}
