<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/login',
        summary: 'Iniciar sesión y obtener token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@sidb.local'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123...'),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'email', type: 'string', format: 'email'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Credenciales incorrectas', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/Errores422')),
        ],
    )]
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        if (! $user->puedeUsarApi()) {
            return response()->json([
                'message' => 'Acceso denegado. Tu cuenta no está vinculada a un perfil de vendedor o cobrador activo.',
            ], 403);
        }

        // El teléfono es de la empresa, pero se pierde/roba/daña en campo —
        // un token que nunca vence dejaría acceso completo a la API activo
        // para siempre si eso pasa. 60 días es suficiente para no forzar
        // reinicios de sesión seguidos (el PIN local ya cubre el candado del
        // día a día) y acota el daño de un teléfono perdido sin depender de
        // que alguien recuerde revocarlo a mano.
        $token = $user->createToken('pos-token', ['*'], now()->addDays(60))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'roles'  => $user->getRoleNames(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/me',
        summary: 'Obtener usuario autenticado con roles y permisos',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Datos del usuario actual',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'permisos', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(
                            property: 'sucursales',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'nombre', type: 'string'),
                                ],
                                type: 'object',
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('sucursales', 'vendedor', 'cobrador', 'supervisor');

        return response()->json([
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'roles'     => $user->getRoleNames(),
            'permisos'  => $user->getAllPermissions()->pluck('name'),
            'sucursales'=> $user->sucursales->map(fn ($s) => ['id' => $s->id, 'nombre' => $s->nombre]),
            'perfil'    => [
                'es_vendedor'  => $user->esVendedor(),
                'es_cobrador'  => $user->esCobrador(),
                'es_supervisor'=> $user->esSupervisor(),
                'vendedor'     => $user->vendedor ? [
                    'id'          => $user->vendedor->id,
                    'codigo'      => $user->vendedor->codigo,
                    'nombre'      => $user->vendedor->nombre_completo,
                    'sucursal_id' => $user->vendedor->sucursal_id,
                    'es_cobrador' => $user->vendedor->es_cobrador,
                ] : null,
                'cobrador'     => $user->cobrador ? [
                    'id'          => $user->cobrador->id,
                    'nombre'      => $user->cobrador->nombre_completo,
                    'sucursal_id' => $user->cobrador->sucursal_id,
                ] : null,
                'supervisor'   => $user->supervisor ? [
                    'id'          => $user->supervisor->id,
                    'nombre'      => $user->supervisor->nombre_completo,
                    'sucursal_id' => $user->supervisor->sucursal_id,
                ] : null,
            ],
        ]);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Cerrar sesión y revocar token actual',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
