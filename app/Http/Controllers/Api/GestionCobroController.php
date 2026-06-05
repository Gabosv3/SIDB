<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class GestionCobroController extends Controller
{
    #[OA\Get(
        path: '/gestiones-cobro/pendientes',
        summary: 'Listar gestiones de cobro pendientes',
        security: [['sanctum' => []]],
        tags: ['Gestiones de Cobro'],
        parameters: [
            new OA\Parameter(name: 'cliente_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'vencidas', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de gestiones pendientes'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function pendientes(Request $request): JsonResponse
    {
        $query = GestionCobro::with(['cliente', 'venta'])
            ->whereIn('estado', ['pendiente', 'parcialmente_cobrado']);

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->integer('cliente_id'));
        }

        if ($request->boolean('vencidas')) {
            $query->whereDate('fecha_vencimiento', '<', today());
        }

        return response()->json(
            $query->orderBy('fecha_vencimiento')->get()
        );
    }

    #[OA\Get(
        path: '/gestiones-cobro/{id}',
        summary: 'Obtener detalle de una gestión de cobro',
        security: [['sanctum' => []]],
        tags: ['Gestiones de Cobro'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la gestión'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrada'),
        ],
    )]
    public function show(int $id): JsonResponse
    {
        $gestion = GestionCobro::with(['cliente', 'venta'])->findOrFail($id);
        return response()->json($gestion);
    }

    #[OA\Post(
        path: '/gestiones-cobro/{id}/pagar',
        summary: 'Registrar un pago para una gestión de cobro',
        security: [['sanctum' => []]],
        tags: ['Gestiones de Cobro'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['monto', 'metodo_pago'],
                properties: [
                    new OA\Property(property: 'monto', type: 'number', format: 'float', example: 14.00, description: 'Monto a pagar'),
                    new OA\Property(property: 'metodo_pago', type: 'string', enum: ['efectivo', 'transferencia', 'cheque', 'deposito'], example: 'efectivo'),
                    new OA\Property(property: 'referencia', type: 'string', nullable: true, example: 'TRF-00123'),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pago registrado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Gestión no encontrada'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function pagar(Request $request, int $id): JsonResponse
    {
        $gestion = GestionCobro::findOrFail($id);

        $data = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:efectivo,transferencia,cheque,deposito',
            'referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $monto = (float)$data['monto'];

        // Validar que no haya saldo pendiente en cuota anterior
        if ($gestion->numero_cuota > 1) {
            $cuotaAnterior = $gestion->venta->gestionesCobro()
                ->where('numero_cuota', $gestion->numero_cuota - 1)
                ->first();

            $saldoPendiente = $cuotaAnterior->monto_cuota - $cuotaAnterior->monto_pagado;
            if ($saldoPendiente > 0) {
                throw ValidationException::withMessages([
                    'monto' => "Aún hay \${$saldoPendiente} pendiente en la cuota anterior",
                ]);
            }
        }

        // Calcular saldo pendiente después del pago
        $saldoPendiente = $gestion->monto_cuota - ($gestion->monto_pagado + $monto);

        // Validar que no se pague más de lo debido
        if ($saldoPendiente < 0) {
            $pendiente = $gestion->monto_cuota - $gestion->monto_pagado;
            throw ValidationException::withMessages([
                'monto' => "El monto no puede exceder \${$pendiente} pendiente",
            ]);
        }

        // Crear registro en PagoVenta
        PagoVenta::create([
            'venta_id' => $gestion->venta_id,
            'cliente_id' => $gestion->cliente_id,
            'monto' => $monto,
            'fecha_pago' => today(),
            'metodo_pago' => $data['metodo_pago'],
            'referencia' => $data['referencia'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'user_id' => auth()->id(),
        ]);

        // Actualizar monto_pagado
        $nuevoMontoPagado = $gestion->monto_pagado + $monto;

        // Determinar estado
        if ($nuevoMontoPagado >= $gestion->monto_cuota) {
            $estado = 'cobrado';
        } else {
            $estado = 'parcialmente_cobrado';
        }

        // Actualizar GestionCobro
        $gestion->update([
            'monto_pagado' => $nuevoMontoPagado,
            'estado' => $estado,
        ]);

        // Actualizar saldo pendiente de la venta
        $venta = $gestion->venta;
        $venta->monto_pagado += $monto;
        $venta->saldo_pendiente = max(0, $venta->saldo_pendiente - $monto);
        if ($venta->saldo_pendiente == 0) {
            $venta->estado = 'completada';
        }
        $venta->save();

        return response()->json([
            'mensaje' => 'Pago registrado exitosamente',
            'gestion' => $gestion->fresh(),
            'venta' => $venta->fresh(),
        ], 200);
    }
}
