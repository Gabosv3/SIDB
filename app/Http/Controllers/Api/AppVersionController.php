<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AppVersionController extends Controller
{
    #[OA\Get(
        path: '/version',
        summary: 'Última versión publicada del APK del POS móvil',
        description: 'Endpoint público (sin autenticación) para que la app consulte al abrir si hay una '
            .'actualización disponible. Los valores se administran desde Personalización del Sistema.',
        tags: ['App'],
        responses: [
            new OA\Response(response: 200, description: 'Datos de la última versión publicada'),
        ],
    )]
    public function actual(): JsonResponse
    {
        $config = ConfiguracionSistema::instance();

        return response()->json([
            'version' => $config->apk_version,
            'url' => $config->apk_url,
            'notas' => $config->apk_notas,
        ]);
    }
}
