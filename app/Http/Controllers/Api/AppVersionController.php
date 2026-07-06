<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use OpenApi\Attributes as OA;

class AppVersionController extends Controller
{
    #[OA\Get(
        path: '/version',
        summary: 'Última versión publicada del APK del POS móvil',
        description: 'Endpoint público (sin autenticación) para que la app consulte al abrir si hay una '
            .'actualización disponible. Lee primero public/update/version.json (lo publica /admin/update); '
            .'si no existe todavía, cae de vuelta a los campos de Personalización del Sistema.',
        tags: ['App'],
        responses: [
            new OA\Response(response: 200, description: 'Datos de la última versión publicada'),
        ],
    )]
    public function actual(): JsonResponse
    {
        $archivo = public_path('update/version.json');

        if (File::exists($archivo)) {
            $version = json_decode(File::get($archivo), true);

            if (is_array($version)) {
                return response()->json([
                    'version' => $version['version'] ?? null,
                    'url' => $version['url'] ?? null,
                    'notas' => $version['notas'] ?? null,
                ]);
            }
        }

        $config = ConfiguracionSistema::instance();

        return response()->json([
            'version' => $config->apk_version,
            'url' => $config->apk_url,
            'notas' => $config->apk_notas,
        ]);
    }
}
