<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagoVenta;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PagoVentaController extends Controller
{
    #[OA\Get(
        path: '/ventas/{venta}/pagos',
        summary: 'Listar pagos de una venta',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        parameters: [
            new OA\Parameter(name: 'venta', in: 'path', description: 'ID de la venta', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de pagos',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/PagoVenta')),
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Venta no encontrada'),
        ],
    )]
    public function index(int $venta): JsonResponse
    {
        $v = Venta::findOrFail($venta);

        return response()->json($v->pagos()->latest('fecha_pago')->get());
    }

    #[OA\Post(
        path: '/ventas/{venta}/pagos',
        summary: 'Registrar un pago sobre una venta',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        parameters: [
            new OA\Parameter(name: 'venta', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['monto', 'metodo_pago'],
                properties: [
                    new OA\Property(property: 'monto', type: 'number', format: 'float', example: 100.00),
                    new OA\Property(property: 'fecha_pago', type: 'string', format: 'date', example: '2026-06-01', nullable: true),
                    new OA\Property(property: 'metodo_pago', type: 'string', enum: ['efectivo', 'transferencia', 'tarjeta', 'cheque']),
                    new OA\Property(property: 'referencia', type: 'string', nullable: true),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pago registrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'pago', ref: '#/components/schemas/PagoVenta'),
                        new OA\Property(property: 'saldo_pendiente', type: 'number', format: 'float'),
                        new OA\Property(property: 'estado', type: 'string'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Venta ya pagada o validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(Request $request, int $venta): JsonResponse
    {
        $v = Venta::findOrFail($venta);

        if ((float) $v->saldo_pendiente <= 0) {
            return response()->json(['message' => 'Esta venta ya está completamente pagada.'], 422);
        }

        $data = $request->validate([
            'monto'        => 'required|numeric|min:0.01|max:' . $v->saldo_pendiente,
            'fecha_pago'   => 'nullable|date',
            'metodo_pago'  => 'required|in:efectivo,transferencia,tarjeta,cheque',
            'referencia'   => 'nullable|string|max:100',
            'observaciones'=> 'nullable|string|max:500',
        ]);

        $pago = PagoVenta::create([
            'venta_id'     => $v->id,
            'cliente_id'   => $v->cliente_id,
            'user_id'      => $request->user()->id,
            'monto'        => $data['monto'],
            'fecha_pago'   => $data['fecha_pago'] ?? now()->toDateString(),
            'metodo_pago'  => $data['metodo_pago'],
            'referencia'   => $data['referencia'] ?? null,
            'observaciones'=> $data['observaciones'] ?? null,
        ]);

        $v->refresh();

        return response()->json([
            'pago'           => $pago,
            'saldo_pendiente'=> $v->saldo_pendiente,
            'estado'         => $v->estado,
        ], 201);
    }
}
