<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PushTokenController extends Controller
{
    #[OA\Post(
        path: '/push-tokens',
        summary: 'Registrar el token de push notifications del dispositivo',
        description: 'La app llama esto al iniciar sesión (y cada vez que el token cambie). Un mismo token físico solo puede pertenecer a un usuario a la vez — si el dispositivo cambió de dueño, el token se reasigna automáticamente.',
        security: [['sanctum' => []]],
        tags: ['Notificaciones'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]'),
                    new OA\Property(property: 'platform', type: 'string', nullable: true, example: 'android'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token registrado'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => 'required|string|max:255',
            'platform' => 'nullable|string|max:20',
        ]);

        PushToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'          => $request->user()->id,
                'platform'         => $data['platform'] ?? null,
                'ultima_actividad' => now(),
            ]
        );

        return response()->json(['mensaje' => 'Token registrado.']);
    }

    #[OA\Delete(
        path: '/push-tokens',
        summary: 'Eliminar el token de push notifications del dispositivo',
        description: 'La app lo llama al cerrar sesión, para dejar de recibir notificaciones en un dispositivo compartido.',
        security: [['sanctum' => []]],
        tags: ['Notificaciones'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(required: ['token'], properties: [
                new OA\Property(property: 'token', type: 'string'),
            ]),
        ),
        responses: [new OA\Response(response: 200, description: 'Token eliminado')],
    )]
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string']);

        PushToken::where('token', $data['token'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['mensaje' => 'Token eliminado.']);
    }
}
