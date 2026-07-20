<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsignacionDiaria;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ProductoController extends Controller
{
    #[OA\Get(
        path: '/productos',
        summary: 'Listar productos activos',
        description: 'Soporta búsqueda por nombre o código y filtros por categoría / sucursal.',
        security: [['sanctum' => []]],
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Texto a buscar en nombre o código', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'categoria_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sucursal_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de productos',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/Paginacion'),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/Producto'),
                                ),
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
        $user     = $request->user();
        $vendedor = $user->vendedor;

        // Si el usuario es vendedor, solo mostrar productos de su asignación de hoy
        if ($vendedor) {
            $asignacion = AsignacionDiaria::where('vendedor_id', $vendedor->id)
                ->where('fecha', today())
                ->where('estado', 'activa')
                ->first();

            if (! $asignacion) {
                return response()->json([
                    'message' => 'No tienes productos asignados para hoy. Contacta a tu supervisor.',
                    'data'    => [],
                ], 200);
            }

            $query = Producto::with('categoria:id,nombre')
                ->join('detalle_asignaciones', function ($join) use ($asignacion) {
                    $join->on('productos.id', '=', 'detalle_asignaciones.producto_id')
                        ->where('detalle_asignaciones.asignacion_id', $asignacion->id)
                        ->whereColumn('detalle_asignaciones.cantidad_asignada', '>', 'detalle_asignaciones.cantidad_vendida');
                })
                ->where('productos.activo', true)
                ->select([
                    'productos.id',
                    'productos.nombre',
                    'productos.codigo',
                    'productos.descripcion',
                    'productos.unidad_medida',
                    'productos.precio_venta',
                    'productos.precios_cuotas',
                    'productos.stock',
                    'detalle_asignaciones.cantidad_asignada as stock_asignado',
                    DB::raw('(detalle_asignaciones.cantidad_asignada - detalle_asignaciones.cantidad_vendida) as stock_disponible'),
                    'detalle_asignaciones.cantidad_vendida as cantidad_vendida',
                    'productos.activo',
                    'productos.categoria_id',
                    'productos.sucursal_id',
                    'productos.imagen',
                ]);
        } else {
            // Cobrador puro: puede ver catálogo completo (para consulta)
            $query = Producto::with('categoria:id,nombre')
                ->where('activo', true)
                ->select([
                    'productos.id',
                    'productos.nombre',
                    'productos.codigo',
                    'productos.descripcion',
                    'productos.unidad_medida',
                    'productos.precio_venta',
                    'productos.precios_cuotas',
                    'productos.stock',
                    'productos.activo',
                    'productos.categoria_id',
                    'productos.sucursal_id',
                    'productos.imagen',
                ]);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->integer('categoria_id'));
        }

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->integer('sucursal_id'));
        }

        $porPagina = min($request->integer('per_page', 50), 100);
        $productos = $query->paginate($porPagina);

        if ($vendedor) {
            $productos->getCollection()->transform(fn (Producto $producto) => [
                'id'               => $producto->id,
                'nombre'           => $producto->nombre,
                'codigo'           => $producto->codigo,
                'descripcion'      => $producto->descripcion,
                'unidad_medida'    => $producto->unidad_medida,
                'precio_venta'     => $producto->precio_venta,
                'precios_cuotas'   => $producto->precios_cuotas,
                'stock_global'     => $producto->stock,
                // Ya vienen calculadas correctamente por el JOIN de arriba, scoped
                // a la asignación de hoy de este vendedor — no usar la relación
                // detalleAsignaciones() aquí, que no está filtrada por asignación
                // y podría traer datos de otro vendedor o de otro día.
                'stock_asignado'   => (int) ($producto->stock_asignado ?? 0),
                'stock_disponible' => (int) ($producto->stock_disponible ?? 0),
                'cantidad_vendida' => (int) ($producto->cantidad_vendida ?? 0),
                'activo'           => $producto->activo,
                'categoria'        => $producto->categoria?->nombre,
                'categoria_id'     => $producto->categoria_id,
                'sucursal_id'      => $producto->sucursal_id,
                'imagen'           => $producto->imagen,
            ]);
        }

        return response()->json($productos);
    }

    #[OA\Get(
        path: '/productos/{id}/metodos-pago',
        summary: 'Obtener métodos de pago a cuotas por producto',
        security: [['sanctum' => []]],
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Opciones de pago a cuotas',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 5),
                        new OA\Property(property: 'nombre', type: 'string', example: 'Televisor 40"'),
                        new OA\Property(property: 'codigo', type: 'string', example: 'TV-40-SAMSUNG'),
                        new OA\Property(property: 'precio_venta', type: 'number', format: 'float', example: 299.99),
                        new OA\Property(property: 'precios_cuotas', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ],
    )]
    public function paymentOptions(Request $request, int $id): JsonResponse
    {
        $vendedor = $request->user()->vendedor;

        if ($vendedor) {
            $asignacion = AsignacionDiaria::where('vendedor_id', $vendedor->id)
                ->where('fecha', today())
                ->where('estado', 'activa')
                ->first();

            abort_if(
                ! $asignacion || ! $asignacion->detalles()->where('producto_id', $id)->exists(),
                404
            );
        }

        $producto = Producto::where('activo', true)->findOrFail($id);

        return response()->json([
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo,
            'precio_venta' => $producto->precio_venta,
            'precios_cuotas' => $producto->precios_cuotas ?? [],
        ]);
    }

    #[OA\Get(
        path: '/productos/{id}',
        summary: 'Obtener un producto por ID',
        security: [['sanctum' => []]],
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Producto encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Producto')),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ],
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $vendedor = $request->user()->vendedor;

        if ($vendedor) {
            $asignacion = AsignacionDiaria::where('vendedor_id', $vendedor->id)
                ->where('fecha', today())
                ->where('estado', 'activa')
                ->first();

            abort_if(
                ! $asignacion || ! $asignacion->detalles()->where('producto_id', $id)->exists(),
                404
            );
        }

        $producto = Producto::with('categoria:id,nombre', 'sucursal:id,nombre')
            ->where('activo', true)
            ->findOrFail($id);

        return response()->json($producto);
    }
}
