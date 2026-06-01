<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoriaController extends Controller
{
    #[OA\Get(
        path: '/categorias',
        summary: 'Listar categorías activas',
        security: [['sanctum' => []]],
        tags: ['Categorías'],
        parameters: [
            new OA\Parameter(name: 'sucursal_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de categorías',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'nombre', type: 'string'),
                            new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                            new OA\Property(property: 'icono', type: 'string', nullable: true),
                        ],
                        type: 'object',
                    ),
                ),
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Categoria::query()->where('activo', true);

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->integer('sucursal_id'));
        }

        return response()->json(
            $query->select('id', 'nombre', 'descripcion', 'icono')->get()
        );
    }
}
