<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetallePreventa;
use App\Models\Preventa;
use App\Models\Producto;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class PreventaController extends Controller
{
    #[OA\Get(
        path: '/preventas',
        summary: 'Listar preventas',
        description: 'Un cobrador ve las preventas que él registró. Un vendedor ve las que un administrador le asignó.',
        security: [['sanctum' => []]],
        tags: ['Preventas'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pendiente', 'convertida', 'rechazada'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada de preventas'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $user     = $request->user();
        $cobrador = $user->cobrador;
        $vendedor = $user->vendedor;

        $query = Preventa::with([
            'cliente:id,nombre,apellido,codigo_anterior,telefono_normal',
            'vendedor:id,nombre,apellido,codigo',
            'detalles.producto:id,nombre,codigo',
        ])
            ->where(function ($q) use ($cobrador, $vendedor, $user) {
                if ($cobrador) {
                    $q->orWhere('user_id', $user->id);
                }

                if ($vendedor) {
                    $q->orWhere('vendedor_id', $vendedor->id);
                }

                if (! $cobrador && ! $vendedor) {
                    $q->whereRaw('1 = 0');
                }
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado));

        $preventas = $query->latest()->paginate($request->integer('per_page', 20));

        $preventas->getCollection()->transform(fn (Preventa $p) => [
            'id'             => $p->id,
            'cliente'        => $p->cliente ? [
                'id'              => $p->cliente->id,
                'nombre'          => $p->cliente->nombre_completo,
                'codigo_anterior' => $p->cliente->codigo_anterior,
                'telefono'        => $p->cliente->telefono_normal,
            ] : null,
            'vendedor'       => $p->vendedor ? [
                'id'     => $p->vendedor->id,
                'nombre' => $p->vendedor->nombre . ' ' . $p->vendedor->apellido,
                'codigo' => $p->vendedor->codigo,
            ] : null,
            'estado'         => $p->estado,
            'monto_estimado' => (float) $p->monto_estimado,
            'observaciones'  => $p->observaciones,
            'fecha'          => $p->fecha->toDateString(),
            'productos'      => $p->detalles->map(fn ($d) => [
                'producto_id'     => $d->producto_id,
                'nombre'          => $d->producto?->nombre,
                'codigo'          => $d->producto?->codigo,
                'cantidad'        => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal'        => (float) $d->subtotal,
            ])->values(),
        ]);

        return response()->json($preventas);
    }

    #[OA\Post(
        path: '/preventas',
        summary: 'Registrar una preventa',
        description: 'El cobrador registra el interés de un cliente en comprar (productos y cantidades del catálogo). Queda pendiente de que un administrador la asigne a un vendedor, que la visitará para cerrar la venta real.',
        security: [['sanctum' => []]],
        tags: ['Preventas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cliente_id', 'detalles'],
                properties: [
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'detalles',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'producto_id', type: 'integer'),
                                new OA\Property(property: 'cantidad', type: 'integer', minimum: 1),
                            ],
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Preventa registrada'),
            new OA\Response(response: 403, description: 'Sin perfil de cobrador'),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/Errores422')),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id'              => 'required|exists:clientes,id',
            'observaciones'           => 'nullable|string|max:500',
            'detalles'                => 'required|array|min:1',
            'detalles.*.producto_id'  => 'required|integer|exists:productos,id',
            'detalles.*.cantidad'     => 'required|integer|min:1',
            'idempotency_key'         => 'nullable|string|max:80',
        ]);

        return IdempotencyService::manejar(
            $request->user()->id,
            'preventas.store',
            $data['idempotency_key'] ?? null,
            fn () => $this->crearPreventa($request, $data)
        );
    }

    private function crearPreventa(Request $request, array $data): JsonResponse
    {
        $cobrador = $request->user()->cobrador;

        if (! $cobrador) {
            return response()->json(['mensaje' => 'No se encontró perfil de cobrador.'], 403);
        }

        $preventa = DB::transaction(function () use ($data, $request, $cobrador) {
            $productos = Producto::whereIn('id', collect($data['detalles'])->pluck('producto_id'))
                ->where('activo', true)
                ->get()
                ->keyBy('id');

            $detallesPrep = [];
            $montoEstimado = 0;

            foreach ($data['detalles'] as $item) {
                $producto = $productos->get($item['producto_id']);

                if (! $producto) {
                    throw ValidationException::withMessages([
                        'detalles' => "El producto {$item['producto_id']} no está disponible.",
                    ]);
                }

                $subtotal = round((float) $producto->precio_venta * $item['cantidad'], 2);
                $montoEstimado += $subtotal;

                $detallesPrep[] = [
                    'producto_id'     => $producto->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal'        => $subtotal,
                ];
            }

            $preventa = Preventa::create([
                'cliente_id'     => $data['cliente_id'],
                'user_id'        => $request->user()->id,
                'sucursal_id'    => $cobrador->sucursal_id,
                'estado'         => 'pendiente',
                'monto_estimado' => round($montoEstimado, 2),
                'observaciones'  => $data['observaciones'] ?? null,
            ]);

            foreach ($detallesPrep as $d) {
                DetallePreventa::create(array_merge(['preventa_id' => $preventa->id], $d));
            }

            return $preventa->load('detalles.producto:id,nombre,codigo');
        });

        return response()->json([
            'mensaje'   => 'Preventa registrada. Un administrador la asignará a un vendedor.',
            'preventa'  => [
                'id'             => $preventa->id,
                'estado'         => $preventa->estado,
                'monto_estimado' => (float) $preventa->monto_estimado,
                'productos'      => $preventa->detalles->map(fn ($d) => [
                    'nombre'   => $d->producto?->nombre,
                    'cantidad' => (int) $d->cantidad,
                    'subtotal' => (float) $d->subtotal,
                ])->values(),
            ],
        ], 201);
    }
}
