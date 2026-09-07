<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Garantia;
use App\Models\Venta;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GarantiaController extends Controller
{
    private function sucursalIdDelUsuario($user): ?int
    {
        return $user->vendedor?->sucursal_id ?? $user->cobrador?->sucursal_id;
    }

    #[OA\Get(
        path: '/garantias',
        summary: 'Listar las garantías reportadas por el cobrador autenticado',
        security: [['sanctum' => []]],
        tags: ['Garantías'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pendiente', 'en_proceso', 'resuelta', 'rechazada'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de garantías'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Garantia::where('reportado_por', $request->user()->id)
            ->with('cliente:id,nombre,apellido', 'venta:id,numero_venta')
            ->latest();

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        $garantias = $query->get()->map(fn (Garantia $g) => [
            'id'          => $g->id,
            'cliente'     => trim(($g->cliente?->nombre ?? '').' '.($g->cliente?->apellido ?? '')),
            'venta'       => $g->venta?->numero_venta,
            'motivo'      => $g->motivo,
            'descripcion' => $g->descripcion,
            'resolucion'  => $g->resolucion,
            'estado'      => $g->estado,
            'fotos'       => $g->fotos_urls,
            'fecha_reporte' => $g->fecha_reporte->toDateString(),
            'creado'      => $g->created_at->format('d/m/Y H:i'),
        ]);

        return response()->json($garantias);
    }

    #[OA\Post(
        path: '/garantias',
        summary: 'Reportar un problema de garantía en un producto vendido',
        description: 'El cobrador la reporta al visitar al cliente. A diferencia de un reintegro, NO saca al cliente de su ruta de cobro — sigue pagando su cuota normalmente mientras se revisa. Queda "pendiente" hasta que un administrador la asigne a alguien para resolverla.',
        security: [['sanctum' => []]],
        tags: ['Garantías'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['venta_id', 'descripcion'],
                    properties: [
                        new OA\Property(property: 'venta_id', type: 'integer', example: 20),
                        new OA\Property(property: 'motivo', type: 'string', nullable: true, example: 'No enfría'),
                        new OA\Property(property: 'descripcion', type: 'string', example: 'El refrigerador no enfría desde hace una semana'),
                        new OA\Property(property: 'fotos', type: 'array', items: new OA\Items(type: 'string', format: 'binary'), nullable: true, description: 'Hasta 5 fotos del producto dañado'),
                        new OA\Property(property: 'idempotency_key', type: 'string', nullable: true, maxLength: 80),
                    ],
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Garantía reportada'),
            new OA\Response(response: 403, description: 'La venta no pertenece a un cliente de tus rutas'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'venta_id'        => 'required|integer|exists:ventas,id',
            'motivo'          => 'nullable|string|max:255',
            'descripcion'     => 'required|string|max:1000',
            'fotos'           => 'nullable|array|max:5',
            'fotos.*'         => 'image|mimes:jpeg,png,webp|max:4096',
            'idempotency_key' => 'nullable|string|max:80',
        ]);

        return IdempotencyService::manejar(
            $request->user()->id,
            'garantias.store',
            $data['idempotency_key'] ?? null,
            fn () => $this->crearGarantia($request, $data)
        );
    }

    private function crearGarantia(Request $request, array $data): JsonResponse
    {
        $user = $request->user();
        $venta = Venta::with('cliente.rutaCobro')->findOrFail($data['venta_id']);

        // El cobrador solo puede reportar garantías de ventas de clientes que
        // realmente están en sus rutas — mismo criterio que el resto de la
        // API de cobros, para que no se puedan reportar ventas ajenas.
        $cobrador = $user->cobrador;
        if ($cobrador) {
            $rutasIds = $cobrador->rutasCobro()->pluck('id');
            $perteneceARuta = $venta->cliente && in_array($venta->cliente->ruta_cobro_id, $rutasIds->all(), true);
            if (! $perteneceARuta) {
                return response()->json(['mensaje' => 'Esta venta no pertenece a un cliente de tus rutas.'], 403);
            }
        }

        $fotos = [];
        foreach ($request->file('fotos', []) as $foto) {
            $fotos[] = $foto->store('garantias', 'public');
        }

        $garantia = Garantia::create([
            'venta_id'      => $venta->id,
            'cliente_id'    => $venta->cliente_id,
            'sucursal_id'   => $this->sucursalIdDelUsuario($user) ?? $venta->sucursal_id,
            'reportado_por' => $user->id,
            // El cobrador que debe pasar a recoger el producto es el de la
            // ruta del cliente en este momento — normalmente es el mismo que
            // está reportando la garantía en la visita, pero se guarda por
            // separado de "reportado_por" porque un supervisor o vendedor
            // también podría reportarla sin ser quien recoge.
            'cobrador_id'   => $venta->cliente?->rutaCobro?->cobrador_id,
            'estado'        => 'pendiente',
            'motivo'        => $data['motivo'] ?? null,
            'descripcion'   => $data['descripcion'],
            'fotos'         => $fotos ?: null,
            'fecha_reporte' => now()->toDateString(),
        ]);

        return response()->json([
            'mensaje'   => 'Garantía reportada. Un administrador la asignará para su revisión.',
            'garantia'  => [
                'id'          => $garantia->id,
                'venta_id'    => $garantia->venta_id,
                'numero_venta'=> $venta->numero_venta,
                'cliente'     => $venta->cliente?->nombre_completo,
                'estado'      => $garantia->estado,
                'fecha_reporte' => $garantia->fecha_reporte->toDateString(),
            ],
        ], 201);
    }
}
