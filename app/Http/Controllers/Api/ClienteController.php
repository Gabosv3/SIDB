<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ClienteController extends Controller
{
    /**
     * Acota el query a los clientes que el usuario autenticado puede ver/editar:
     * un cobrador solo los de sus propias rutas, un vendedor solo los de su
     * propia sucursal (un usuario que es ambos ve la unión de los dos). Evita
     * que cualquier perfil POS pueda leer/editar clientes ajenos por ID (IDOR).
     */
    private function scopeClientesDelUsuario(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $huboScope = false;

            if ($cobrador = $user->cobrador) {
                $rutasIds = $cobrador->rutasCobro()->pluck('id');
                $q->orWhereIn('ruta_cobro_id', $rutasIds);
                $huboScope = true;
            }

            if ($vendedor = $user->vendedor) {
                $q->orWhere('sucursal_id', $vendedor->sucursal_id);
                $huboScope = true;
            }

            if (! $huboScope) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    #[OA\Get(
        path: '/clientes',
        summary: 'Listar y buscar clientes activos',
        description: 'Permite buscar por nombre, apellido, DUI, código anterior o teléfono. Devuelve resultados paginados.',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                description: 'Buscar por nombre, apellido, DUI, código anterior o teléfono',
                required: false,
                schema: new OA\Schema(type: 'string', example: '7338')
            ),
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

        $this->scopeClientesDelUsuario($query, $request->user());

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('apellido', 'like', "%{$q}%")
                    ->orWhere('dui', 'like', "%{$q}%")
                    ->orWhere('codigo_anterior', 'like', "%{$q}%")
                    ->orWhere('telefono_normal', 'like', "%{$q}%")
                    ->orWhere('telefono_whatsapp', 'like', "%{$q}%");
            });
        }

        $porPagina = min($request->integer('per_page', 50), 100);

        $clientes = $query->select([
            'id', 'nombre', 'apellido', 'dui', 'codigo_anterior',
            'telefono_normal', 'telefono_whatsapp', 'email', 'direccion',
            'limite_credito', 'saldo', 'activo', 'sucursal_id',
        ])->paginate($porPagina);

        return response()->json($clientes);
    }

    #[OA\Post(
        path: '/clientes',
        summary: 'Crear un nuevo cliente',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['nombre', 'apellido'],
                    properties: [
                        new OA\Property(property: 'nombre', type: 'string', example: 'Maria'),
                        new OA\Property(property: 'apellido', type: 'string', example: 'Hernandez'),
                        new OA\Property(property: 'dui', type: 'string', example: '02741470-9'),
                        new OA\Property(property: 'codigo_anterior', type: 'string', example: '7338'),
                        new OA\Property(property: 'telefono_normal', type: 'string', example: '77856915'),
                        new OA\Property(property: 'telefono_whatsapp', type: 'string', example: '77856915'),
                        new OA\Property(property: 'email', type: 'string', example: 'maria@email.com'),
                        new OA\Property(property: 'direccion', type: 'string'),
                        new OA\Property(property: 'departamento', type: 'string'),
                        new OA\Property(property: 'municipio', type: 'string'),
                        new OA\Property(property: 'latitud', type: 'number', format: 'float', example: 13.945),
                        new OA\Property(property: 'longitud', type: 'number', format: 'float', example: -87.87),
                        new OA\Property(property: 'ruta_cobro_id', type: 'integer'),
                        new OA\Property(property: 'foto_casa', type: 'string', format: 'binary', description: 'Foto de la casa (JPG, PNG, WEBP, máx 4MB)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente creado'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'dui'              => 'nullable|string|max:20|unique:clientes,dui',
            'codigo_anterior'  => 'nullable|string|max:50',
            'telefono_normal'  => 'nullable|string|max:20|regex:/^[0-9\-\s]{8,20}$/',
            'telefono_whatsapp'=> 'nullable|string|max:20|regex:/^[0-9\-\s]{8,20}$/',
            'email'            => 'nullable|email|max:100',
            'direccion'        => 'nullable|string|max:255',
            'departamento'     => 'nullable|string|max:100',
            'municipio'        => 'nullable|string|max:100',
            'distrito'         => 'nullable|string|max:100',
            'nombre_conyuge'   => 'nullable|string|max:200',
            'latitud'          => 'nullable|numeric|between:-90,90',
            'longitud'         => 'nullable|numeric|between:-180,180',
            'ruta_cobro_id'    => 'nullable|integer|exists:rutas_cobro,id',
            'foto_casa'        => 'nullable|image|mimes:jpeg,png,webp|max:4096',
            'ref_fam1_nombre'    => 'nullable|string|max:150',
            'ref_fam1_telefono'  => 'nullable|string|max:30',
            'ref_fam1_parentesco'=> 'nullable|string|max:60',
            'ref_fam2_nombre'    => 'nullable|string|max:150',
            'ref_fam2_telefono'  => 'nullable|string|max:30',
            'ref_fam2_parentesco'=> 'nullable|string|max:60',
            'ref_con1_nombre'    => 'nullable|string|max:150',
            'ref_con1_telefono'  => 'nullable|string|max:30',
            'ref_con1_trabajo'   => 'nullable|string|max:150',
            'ref_con2_nombre'    => 'nullable|string|max:150',
            'ref_con2_telefono'  => 'nullable|string|max:30',
            'ref_con2_trabajo'   => 'nullable|string|max:150',
        ]);

        if ($request->hasFile('foto_casa')) {
            $data['foto_casa'] = $request->file('foto_casa')->store('casas', 'public');
        }

        $data['sucursal_id'] = $request->user()->vendedor?->sucursal_id ?? 1;
        $data['activo'] = true;

        $cliente = \App\Models\Cliente::create($data);

        return response()->json([
            'mensaje'  => 'Cliente creado exitosamente.',
            'cliente'  => [
                'id'              => $cliente->id,
                'nombre'          => $cliente->nombre_completo,
                'dui'             => $cliente->dui,
                'codigo_anterior' => $cliente->codigo_anterior,
                'telefono'        => $cliente->telefono_normal,
                'foto_casa'       => $cliente->foto_casa
                    ? asset('storage/' . $cliente->foto_casa)
                    : null,
            ],
        ], 201);
    }

    #[OA\Patch(
        path: '/clientes/{id}/ubicacion',
        summary: 'Actualizar latitud y longitud de un cliente',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['latitud', 'longitud'],
                properties: [
                    new OA\Property(property: 'latitud', type: 'number', format: 'float', example: 13.9450),
                    new OA\Property(property: 'longitud', type: 'number', format: 'float', example: -87.8700),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Ubicación actualizada'),
            new OA\Response(response: 403, description: 'Este cliente no te pertenece'),
            new OA\Response(response: 404, description: 'Cliente no encontrado'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function actualizarUbicacion(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'latitud'  => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ]);

        $cliente = $this->scopeClientesDelUsuario(Cliente::query(), $request->user())->findOrFail($id);
        $cliente->update($data);

        return response()->json([
            'mensaje'  => 'Ubicación actualizada.',
            'cliente'  => [
                'id'       => $cliente->id,
                'nombre'   => $cliente->nombre_completo,
                'latitud'  => (float) $cliente->latitud,
                'longitud' => (float) $cliente->longitud,
            ],
        ]);
    }

    #[OA\Patch(
        path: '/clientes/{id}/telefonos',
        summary: 'Actualizar teléfonos de un cliente',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'telefono_normal', type: 'string', example: '75123456'),
                    new OA\Property(property: 'telefono_whatsapp', type: 'string', example: '75123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Teléfonos actualizados'),
            new OA\Response(response: 403, description: 'Este cliente no te pertenece'),
            new OA\Response(response: 404, description: 'Cliente no encontrado'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function actualizarTelefonos(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'telefono_normal'   => 'nullable|string|max:20|regex:/^[0-9\-\s]{8,20}$/',
            'telefono_whatsapp' => 'nullable|string|max:20|regex:/^[0-9\-\s]{8,20}$/',
        ]);

        $cliente = $this->scopeClientesDelUsuario(Cliente::query(), $request->user())->findOrFail($id);
        $cliente->update($data);

        return response()->json([
            'mensaje' => 'Teléfonos actualizados.',
            'cliente' => [
                'id'                => $cliente->id,
                'nombre'            => $cliente->nombre_completo,
                'telefono_normal'   => $cliente->telefono_normal,
                'telefono_whatsapp' => $cliente->telefono_whatsapp,
            ],
        ]);
    }

    #[OA\Patch(
        path: '/clientes/{id}/nombre',
        summary: 'Actualizar nombre y/o apellido de un cliente',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Maria', description: 'Nuevo nombre (opcional)'),
                    new OA\Property(property: 'apellido', type: 'string', example: 'Hernandez', description: 'Nuevo apellido (opcional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Nombre actualizado'),
            new OA\Response(response: 403, description: 'Este cliente no te pertenece'),
            new OA\Response(response: 404, description: 'Cliente no encontrado'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function actualizarNombre(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'nombre'   => 'nullable|string|max:100',
            'apellido' => 'nullable|string|max:100',
        ]);

        // Validar que al menos uno de los campos esté presente
        if (empty(array_filter($data))) {
            return response()->json([
                'message' => 'Debe proporcionar al menos nombre o apellido.',
            ], 422);
        }

        $cliente = $this->scopeClientesDelUsuario(Cliente::query(), $request->user())->findOrFail($id);
        $cliente->update($data);

        return response()->json([
            'mensaje' => 'Nombre actualizado.',
            'cliente' => [
                'id'       => $cliente->id,
                'nombre'   => $cliente->nombre,
                'apellido' => $cliente->apellido,
                'nombre_completo' => $cliente->nombre_completo,
            ],
        ]);
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
            new OA\Response(response: 403, description: 'Este cliente no te pertenece'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ],
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $query = Cliente::where('activo', true)
            ->select([
                'id', 'nombre', 'apellido', 'dui', 'nit',
                'telefono_normal', 'telefono_whatsapp', 'email',
                'direccion', 'departamento', 'municipio', 'distrito',
                'limite_credito', 'saldo', 'activo', 'sucursal_id', 'ruta_cobro_id',
            ]);

        $cliente = $this->scopeClientesDelUsuario($query, $request->user())->findOrFail($id);

        return response()->json($cliente);
    }

    #[OA\Post(
        path: '/clientes/{id}/vincular',
        summary: 'Vincular dos clientes al mismo grupo familiar',
        description: 'Si ninguno tiene grupo, crea uno nuevo usando el ID de {id}. Si uno ya tiene grupo, el otro lo adopta. Si ambos tienen grupos distintos, se fusionan bajo el grupo de {id}.',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cliente_id_vincular'],
                properties: [
                    new OA\Property(property: 'cliente_id_vincular', type: 'integer', example: 88),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Clientes vinculados'),
            new OA\Response(response: 403, description: 'Alguno de los dos clientes no te pertenece'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function vincular(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'cliente_id_vincular' => 'required|integer|exists:clientes,id',
        ]);

        if ($id === (int) $data['cliente_id_vincular']) {
            return response()->json(['error' => 'No se puede vincular un cliente consigo mismo.'], 422);
        }

        $scope = fn () => $this->scopeClientesDelUsuario(Cliente::query(), $request->user());

        $cliente = $scope()->findOrFail($id);
        $otro = $scope()->findOrFail($data['cliente_id_vincular']);

        if ($cliente->grupo_id && $otro->grupo_id) {
            if ($cliente->grupo_id !== $otro->grupo_id) {
                // Fusiona todo el grupo del otro bajo el grupo de {id} (soporta cadenas de 3+ personas)
                Cliente::where('grupo_id', $otro->grupo_id)->update(['grupo_id' => $cliente->grupo_id]);
            }
        } elseif ($cliente->grupo_id) {
            $otro->update(['grupo_id' => $cliente->grupo_id]);
        } elseif ($otro->grupo_id) {
            $cliente->update(['grupo_id' => $otro->grupo_id]);
        } else {
            $cliente->update(['grupo_id' => $cliente->id]);
            $otro->update(['grupo_id' => $cliente->id]);
        }

        return response()->json([
            'mensaje' => 'Clientes vinculados correctamente.',
            'grupo_id' => $cliente->fresh()->grupo_id,
        ]);
    }

    #[OA\Get(
        path: '/clientes/{id}/grupo',
        summary: 'Ver las cuentas de todo el grupo familiar del cliente',
        description: 'Devuelve al cliente y a sus vinculados con el saldo de cada cuenta a crédito. Si el cliente no tiene grupo, devuelve solo a sí mismo.',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cuentas del grupo'),
            new OA\Response(response: 403, description: 'Este cliente no te pertenece'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ],
    )]
    public function grupo(Request $request, int $id): JsonResponse
    {
        $cliente = $this->scopeClientesDelUsuario(Cliente::query(), $request->user())->findOrFail($id);

        $clientes = $cliente->grupo_id
            ? collect([$cliente])->merge($cliente->vinculados)
            : collect([$cliente]);

        $clientesData = $clientes->map(function (Cliente $c) {
            $ventas = Venta::where('cliente_id', $c->id)
                ->where('tipo_pago', 'credito')
                ->where('saldo_pendiente', '>', 0)
                ->with([
                    'detalles.producto:id,nombre',
                    'gestionesCobro' => fn ($q) => $q->where('estado', 'pendiente')->orderBy('numero_cuota'),
                ])
                ->get();

            $cuentas = $ventas->map(fn (Venta $v) => [
                'venta_id' => $v->id,
                'producto' => $v->detalles->pluck('producto.nombre')->filter()->implode(', ') ?: null,
                'saldo' => (float) $v->saldo_pendiente,
                'cuota_mensual' => (float) ($v->gestionesCobro->first()->monto_cuota ?? 0),
            ])->values();

            return [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'apellido' => $c->apellido,
                'dui' => $c->dui,
                'cuentas' => $cuentas,
                'saldo_total' => (float) $cuentas->sum('saldo'),
            ];
        })->values();

        return response()->json([
            'grupo_id' => $cliente->grupo_id,
            'clientes' => $clientesData,
            'saldo_total_grupo' => (float) $clientesData->sum('saldo_total'),
        ]);
    }

    #[OA\Post(
        path: '/clientes/{id}/desvincular',
        summary: 'Quitar a un cliente de su grupo familiar',
        description: 'Si el grupo queda con un solo integrante, también se le quita el grupo a ese último.',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente desvinculado'),
            new OA\Response(response: 403, description: 'Este cliente no te pertenece'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ],
    )]
    public function desvincular(Request $request, int $id): JsonResponse
    {
        $cliente = $this->scopeClientesDelUsuario(Cliente::query(), $request->user())->findOrFail($id);

        if (! $cliente->grupo_id) {
            return response()->json(['mensaje' => 'El cliente no pertenece a ningún grupo.']);
        }

        $grupoId = $cliente->grupo_id;
        $cliente->update(['grupo_id' => null]);

        $restantes = Cliente::where('grupo_id', $grupoId)->get();
        if ($restantes->count() === 1) {
            $restantes->first()->update(['grupo_id' => null]);
        }

        return response()->json(['mensaje' => 'Cliente desvinculado correctamente.']);
    }
}
