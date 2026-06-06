<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ClienteController extends Controller
{
    #[OA\Get(
        path: '/clientes',
        summary: 'Listar clientes activos',
        description: 'Permite buscar por nombre, apellido, DUI o teléfono.',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Buscar por nombre, apellido, DUI o teléfono', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sucursal_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de clientes',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/Paginacion'),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/Cliente'),
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
        $query = Cliente::query()->where('activo', true);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('apellido', 'like', "%{$q}%")
                    ->orWhere('dui', 'like', "%{$q}%")
                    ->orWhere('telefono_normal', 'like', "%{$q}%")
                    ->orWhere('telefono_whatsapp', 'like', "%{$q}%");
            });
        }

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->integer('sucursal_id'));
        }

        $clientes = $query->select([
            'id', 'nombre', 'apellido', 'dui', 'telefono_normal',
            'telefono_whatsapp', 'email', 'direccion',
            'limite_credito', 'saldo', 'activo', 'sucursal_id',
        ])->paginate($request->integer('per_page', 50));

        return response()->json($clientes);
    }

    #[OA\Get(
        path: '/clientes/{id}',
        summary: 'Obtener un cliente por ID',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Cliente')),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ],
    )]
    public function show(int $id): JsonResponse
    {
        $cliente = Cliente::where('activo', true)
            ->select([
                'id', 'nombre', 'apellido', 'dui', 'nit',
                'telefono_normal', 'telefono_whatsapp', 'email',
                'direccion', 'departamento', 'municipio', 'distrito',
                'limite_credito', 'saldo', 'activo', 'sucursal_id', 'ruta_cobro_id',
            ])
            ->findOrFail($id);

        return response()->json($cliente);
    }
}
