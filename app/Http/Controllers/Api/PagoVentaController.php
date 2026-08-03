<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            new OA\Response(response: 403, description: 'Esta venta no te pertenece'),
            new OA\Response(response: 404, description: 'Venta no encontrada'),
        ],
    )]
    public function index(Request $request, int $venta): JsonResponse
    {
        $v = Venta::where('id', $venta)->where('user_id', $request->user()->id)->firstOrFail();

        return response()->json($v->pagos()->latest('fecha_pago')->get());
    }

    #[OA\Post(
        path: '/ventas/{venta}/pagos',
        summary: 'Registrar un pago sobre una venta',
        description: 'El monto se reparte automáticamente entre las cuotas pendientes más antiguas de la venta, igual que el cobro por cliente.',
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
                        new OA\Property(property: 'saldo_pendiente', type: 'number', format: 'float'),
                        new OA\Property(property: 'estado', type: 'string'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Esta venta no te pertenece'),
            new OA\Response(response: 422, description: 'Venta ya pagada o validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(Request $request, int $venta): JsonResponse
    {
        $v = Venta::where('id', $venta)->where('user_id', $request->user()->id)->firstOrFail();

        if ((float) $v->saldo_pendiente <= 0) {
            return response()->json(['message' => 'Esta venta ya está completamente pagada.'], 422);
        }

        $data = $request->validate([
            'monto'         => 'required|numeric|min:0.01',
            'fecha_pago'    => 'nullable|date',
            'metodo_pago'   => 'required|in:efectivo,transferencia,cheque,deposito',
            'referencia'    => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $resultado = DB::transaction(function () use ($v, $data, $request) {
            // Bloquea la venta para revalidar el saldo con datos frescos (evita
            // sobre-cobro si dos pagos llegan casi al mismo tiempo).
            $ventaLock = Venta::where('id', $v->id)->lockForUpdate()->first();

            $monto = (float) $data['monto'];
            $saldoVenta = (float) $ventaLock->saldo_pendiente;

            if ($monto > $saldoVenta) {
                return ['error' => "El monto \${$monto} supera el saldo pendiente de esta venta (\${$saldoVenta})."];
            }

            // El pago se reparte entre las cuotas pendientes más antiguas, igual
            // que CobroController::pagarCliente — para no dejar los pagos
            // desincronizados del plan de cuotas de la venta.
            $gestiones = GestionCobro::where('venta_id', $ventaLock->id)
                ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                ->orderBy('fecha_vencimiento')
                ->orderBy('numero_cuota')
                ->lockForUpdate()
                ->get();

            $restante = $monto;

            foreach ($gestiones as $gestion) {
                if ($restante <= 0) {
                    break;
                }

                $saldoCuota = round($gestion->monto_cuota - $gestion->monto_pagado, 2);
                $aplicar = min($restante, $saldoCuota);

                PagoVenta::create([
                    'venta_id'      => $ventaLock->id,
                    'cliente_id'    => $ventaLock->cliente_id,
                    'user_id'       => $request->user()->id,
                    'monto'         => $aplicar,
                    'fecha_pago'    => $data['fecha_pago'] ?? now()->toDateString(),
                    'metodo_pago'   => $data['metodo_pago'],
                    'referencia'    => $data['referencia'] ?? null,
                    'observaciones' => $data['observaciones'] ?? null,
                ]);

                $gestion->increment('monto_pagado', $aplicar);
                $gestion->refresh();
                $gestion->update([
                    'estado' => $gestion->monto_pagado >= $gestion->monto_cuota ? 'cobrado' : 'parcialmente_cobrado',
                ]);

                $restante = round($restante - $aplicar, 2);
            }

            // Si no hay (o ya no quedan) cuotas pendientes pero sí saldo en la
            // venta (ej. venta sin plan de cuotas), se registra el resto directo.
            if ($restante > 0) {
                PagoVenta::create([
                    'venta_id'      => $ventaLock->id,
                    'cliente_id'    => $ventaLock->cliente_id,
                    'user_id'       => $request->user()->id,
                    'monto'         => $restante,
                    'fecha_pago'    => $data['fecha_pago'] ?? now()->toDateString(),
                    'metodo_pago'   => $data['metodo_pago'],
                    'referencia'    => $data['referencia'] ?? null,
                    'observaciones' => $data['observaciones'] ?? null,
                ]);
            }

            $ventaLock->refresh();

            return [
                'saldo_pendiente' => (float) $ventaLock->saldo_pendiente,
                'estado'          => $ventaLock->estado,
            ];
        });

        if (isset($resultado['error'])) {
            return response()->json(['message' => $resultado['error']], 422);
        }

        return response()->json($resultado, 201);
    }
}
