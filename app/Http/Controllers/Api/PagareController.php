<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Pagare;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class PagareController extends Controller
{
    #[OA\Post(
        path: '/pagares',
        summary: 'Guardar un pagaré firmado',
        description: 'Sube el PDF del pagaré ya firmado por el cliente y lo enlaza a su ficha. Se genera antes de confirmar la venta a crédito/mixta, por lo que "venta_id" se puede enlazar después con PATCH /pagares/{id}/venta.',
        security: [['sanctum' => []]],
        tags: ['Pagarés'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['cliente_id', 'monto_financiado', 'pdf'],
                    properties: [
                        new OA\Property(property: 'cliente_id', type: 'integer'),
                        new OA\Property(property: 'monto_financiado', type: 'number', format: 'float'),
                        new OA\Property(property: 'nombre_deudor', type: 'string', nullable: true),
                        new OA\Property(property: 'dui', type: 'string', nullable: true),
                        new OA\Property(property: 'direccion', type: 'string', nullable: true),
                        new OA\Property(property: 'lugar_firma', type: 'string', nullable: true),
                        new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'pdf', type: 'string', format: 'binary', description: 'PDF firmado (max 10MB)'),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Pagaré guardado'),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id'         => 'required|integer|exists:clientes,id',
            'monto_financiado'   => 'required|numeric|min:0.01',
            'nombre_deudor'      => 'nullable|string|max:150',
            'dui'                => 'nullable|string|max:20',
            'direccion'          => 'nullable|string|max:255',
            'lugar_firma'        => 'nullable|string|max:100',
            'fecha_vencimiento'  => 'nullable|date',
            'pdf'                => 'required|file|mimes:pdf|max:10240',
        ]);

        $cliente = Cliente::findOrFail($data['cliente_id']);
        $user = $request->user();

        $pdfPath = $request->file('pdf')->store("pagares/{$cliente->id}", 'public');

        $pagare = Pagare::create([
            'cliente_id'        => $cliente->id,
            'user_id'           => $user->id,
            'nombre_deudor'     => $data['nombre_deudor'] ?? $cliente->nombre,
            'dui'               => $data['dui'] ?? $cliente->dui,
            'direccion'         => $data['direccion'] ?? null,
            'lugar_firma'       => $data['lugar_firma'] ?? null,
            'monto_financiado'  => $data['monto_financiado'],
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
            'pdf'               => $pdfPath,
        ]);

        return response()->json([
            'mensaje' => 'Pagaré guardado.',
            'pagare'  => [
                'id'      => $pagare->id,
                'pdf_url' => Storage::url($pdfPath),
            ],
        ], 201);
    }

    #[OA\Patch(
        path: '/pagares/{id}/venta',
        summary: 'Enlazar un pagaré con la venta que lo generó',
        description: 'El pagaré se firma antes de confirmar la venta, así que se enlaza al venta_id justo después de que la venta se registre con éxito.',
        security: [['sanctum' => []]],
        tags: ['Pagarés'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Pagaré enlazado'),
            new OA\Response(response: 403, description: 'El pagaré no pertenece a este usuario'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ],
    )]
    public function vincularVenta(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'venta_id' => 'required|integer|exists:ventas,id',
        ]);

        $pagare = Pagare::findOrFail($id);

        if ($pagare->user_id !== $request->user()->id) {
            return response()->json(['mensaje' => 'Este pagaré no te pertenece.'], 403);
        }

        $venta = Venta::findOrFail($data['venta_id']);
        if ($venta->cliente_id !== $pagare->cliente_id) {
            return response()->json(['mensaje' => 'La venta no corresponde al mismo cliente del pagaré.'], 422);
        }

        $pagare->update(['venta_id' => $venta->id]);

        return response()->json(['mensaje' => 'Pagaré enlazado a la venta.']);
    }
}
