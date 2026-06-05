<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class CobroController extends Controller
{
    private function diaHoy(): string
    {
        $dias = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
        return $dias[now()->dayOfWeekIso];
    }

    private function cobrador(Request $request)
    {
        return $request->user()->cobrador;
    }

    // ── GET /cobros/ruta-hoy ────────────────────────────────────────────────

    #[OA\Get(
        path: '/cobros/ruta-hoy',
        summary: 'Obtener rutas del cobrador para hoy',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        responses: [
            new OA\Response(response: 200, description: 'Rutas del día con conteo de clientes'),
            new OA\Response(response: 404, description: 'Sin ruta para hoy'),
        ],
    )]
    public function rutaHoy(Request $request): JsonResponse
    {
        $cobrador = $this->cobrador($request);
        $diaHoy = $this->diaHoy();

        $rutas = $cobrador->rutasCobro()
            ->where('dia_semana', $diaHoy)
            ->where('activa', true)
            ->withCount('clientes')
            ->with([
                'clientes' => fn ($q) => $q
                    ->select('id', 'nombre', 'apellido', 'telefono_normal', 'saldo', 'ruta_cobro_id')
                    ->withCount([
                        'ventas as gestiones_pendientes' => fn ($q) => $q
                            ->whereHas('gestionesCobro', fn ($g) =>
                                $g->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                            ),
                    ]),
            ])
            ->get();

        if ($rutas->isEmpty()) {
            return response()->json([
                'mensaje' => 'No hay ruta asignada para hoy (' . ucfirst($diaHoy) . ').',
                'dia' => $diaHoy,
            ], 404);
        }

        return response()->json([
            'dia' => $diaHoy,
            'rutas' => $rutas->map(fn ($ruta) => [
                'id' => $ruta->id,
                'nombre' => $ruta->nombre,
                'dia_semana' => $ruta->dia_semana,
                'total_clientes' => $ruta->clientes_count,
                'clientes' => $ruta->clientes->map(fn ($c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre_completo,
                    'telefono' => $c->telefono_normal,
                    'saldo' => $c->saldo,
                    'gestiones_pendientes' => $c->gestiones_pendientes,
                ]),
            ]),
        ]);
    }

    // ── GET /cobros/rutas/{ruta_id}/clientes ─────────────────────────────────

    #[OA\Get(
        path: '/cobros/rutas/{ruta_id}/clientes',
        summary: 'Listar clientes de una ruta específica',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'ruta_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Clientes de la ruta'),
            new OA\Response(response: 403, description: 'Esta ruta no te pertenece'),
            new OA\Response(response: 404, description: 'Ruta no encontrada'),
        ],
    )]
    public function clientesPorRuta(Request $request, int $rutaId): JsonResponse
    {
        $cobrador = $this->cobrador($request);

        $ruta = $cobrador->rutasCobro()
            ->where('id', $rutaId)
            ->where('activa', true)
            ->first();

        if (! $ruta) {
            return response()->json(['mensaje' => 'Ruta no encontrada o no te pertenece.'], 403);
        }

        $clientes = $ruta->clientes()
            ->where('activo', true)
            ->select('id', 'nombre', 'apellido', 'dui', 'telefono_normal', 'telefono_whatsapp', 'saldo', 'direccion', 'municipio', 'departamento')
            ->withCount([
                'ventas as cuotas_pendientes' => fn ($q) => $q
                    ->whereHas('gestionesCobro', fn ($g) =>
                        $g->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                    ),
            ])
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre_completo,
                'dui' => $c->dui,
                'telefono' => $c->telefono_normal,
                'whatsapp' => $c->telefono_whatsapp,
                'saldo' => $c->saldo,
                'direccion' => "{$c->direccion}, {$c->municipio}, {$c->departamento}",
                'cuotas_pendientes' => $c->cuotas_pendientes,
            ]);

        return response()->json([
            'ruta' => ['id' => $ruta->id, 'nombre' => $ruta->nombre],
            'total' => $clientes->count(),
            'clientes' => $clientes,
        ]);
    }

    // ── GET /cobros/clientes/{id} ─────────────────────────────────────────────

    #[OA\Get(
        path: '/cobros/clientes/{id}',
        summary: 'Detalle del cliente con historial de cobros',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del cliente'),
            new OA\Response(response: 403, description: 'Cliente no pertenece a tus rutas'),
        ],
    )]
    public function detalleCliente(Request $request, int $id): JsonResponse
    {
        $cobrador = $this->cobrador($request);

        // Verificar que el cliente pertenece a alguna ruta del cobrador
        $rutasIds = $cobrador->rutasCobro()->pluck('id');
        $cliente = \App\Models\Cliente::where('id', $id)
            ->whereIn('ruta_cobro_id', $rutasIds)
            ->first();

        if (! $cliente) {
            return response()->json(['mensaje' => 'Este cliente no pertenece a tus rutas.'], 403);
        }

        // Historial de gestiones de cobro
        $gestiones = GestionCobro::where('cliente_id', $id)
            ->with('venta:id,numero_venta,total,fecha_venta')
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'venta' => $g->venta?->numero_venta,
                'cuota' => "{$g->numero_cuota}/{$g->total_cuotas}",
                'monto_cuota' => $g->monto_cuota,
                'monto_pagado' => $g->monto_pagado,
                'saldo_cuota' => round($g->monto_cuota - $g->monto_pagado, 2),
                'fecha_vencimiento' => $g->fecha_vencimiento->format('d/m/Y'),
                'estado' => $g->estado,
                'vencida' => $g->fecha_vencimiento->isPast() && $g->estado !== 'cobrado',
            ]);

        return response()->json([
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre_completo,
                'dui' => $cliente->dui,
                'telefono' => $cliente->telefono_normal,
                'whatsapp' => $cliente->telefono_whatsapp,
                'direccion' => $cliente->direccion,
                'saldo_total' => $cliente->saldo,
                'ruta' => $cliente->rutaCobro?->nombre,
            ],
            'resumen' => [
                'total_cuotas' => $gestiones->count(),
                'pendientes' => $gestiones->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])->count(),
                'cobradas' => $gestiones->where('estado', 'cobrado')->count(),
                'vencidas' => $gestiones->where('vencida', true)->count(),
            ],
            'gestiones' => $gestiones,
        ]);
    }

    // ── GET /cobros/clientes/{id}/gestiones-pendientes ────────────────────────

    #[OA\Get(
        path: '/cobros/clientes/{id}/gestiones-pendientes',
        summary: 'Cuotas pendientes o parciales de un cliente',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Gestiones pendientes'),
            new OA\Response(response: 403, description: 'Cliente no pertenece a tus rutas'),
        ],
    )]
    public function gestionesPendientes(Request $request, int $id): JsonResponse
    {
        $cobrador = $this->cobrador($request);
        $rutasIds = $cobrador->rutasCobro()->pluck('id');

        $cliente = \App\Models\Cliente::where('id', $id)
            ->whereIn('ruta_cobro_id', $rutasIds)
            ->first();

        if (! $cliente) {
            return response()->json(['mensaje' => 'Este cliente no pertenece a tus rutas.'], 403);
        }

        $gestiones = GestionCobro::where('cliente_id', $id)
            ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
            ->with('venta:id,numero_venta,total')
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'venta' => $g->venta?->numero_venta,
                'cuota' => "{$g->numero_cuota}/{$g->total_cuotas}",
                'monto_cuota' => $g->monto_cuota,
                'monto_pagado' => $g->monto_pagado,
                'saldo_pendiente' => round($g->monto_cuota - $g->monto_pagado, 2),
                'fecha_vencimiento' => $g->fecha_vencimiento->format('d/m/Y'),
                'estado' => $g->estado,
                'vencida' => $g->fecha_vencimiento->isPast(),
            ]);

        return response()->json([
            'cliente_id' => $id,
            'total_pendiente' => $gestiones->sum('saldo_pendiente'),
            'gestiones' => $gestiones,
        ]);
    }

    // ── POST /cobros/gestiones/{id}/pagar ─────────────────────────────────────

    #[OA\Post(
        path: '/cobros/gestiones/{id}/pagar',
        summary: 'Registrar pago de una cuota',
        security: [['sanctum' => []]],
        tags: ['Cobros'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['monto', 'metodo_pago'],
                properties: [
                    new OA\Property(property: 'monto', type: 'number', format: 'float', example: 14.00),
                    new OA\Property(property: 'metodo_pago', type: 'string', enum: ['efectivo', 'transferencia', 'cheque', 'deposito']),
                    new OA\Property(property: 'referencia', type: 'string', nullable: true),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pago registrado'),
            new OA\Response(response: 403, description: 'Gestión no pertenece a tus rutas'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ],
    )]
    public function pagar(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:efectivo,transferencia,cheque,deposito',
            'referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $cobrador = $this->cobrador($request);
        $rutasIds = $cobrador->rutasCobro()->pluck('id');

        $gestion = GestionCobro::with('venta')->findOrFail($id);

        // Verificar que la gestión pertenece a un cliente del cobrador
        $clientePerteneceARuta = \App\Models\Cliente::where('id', $gestion->cliente_id)
            ->whereIn('ruta_cobro_id', $rutasIds)
            ->exists();

        if (! $clientePerteneceARuta) {
            return response()->json(['mensaje' => 'Esta gestión no pertenece a tus rutas.'], 403);
        }

        $monto = (float) $data['monto'];

        // Validar cuota anterior pendiente
        if ($gestion->numero_cuota > 1) {
            $cuotaAnterior = GestionCobro::where('venta_id', $gestion->venta_id)
                ->where('numero_cuota', $gestion->numero_cuota - 1)
                ->first();

            if ($cuotaAnterior && ($cuotaAnterior->monto_cuota - $cuotaAnterior->monto_pagado) > 0) {
                $pendiente = round($cuotaAnterior->monto_cuota - $cuotaAnterior->monto_pagado, 2);
                throw ValidationException::withMessages([
                    'monto' => "La cuota anterior tiene \${$pendiente} pendiente. Págala primero.",
                ]);
            }
        }

        // Validar que no exceda el saldo pendiente
        $saldoGestion = $gestion->monto_cuota - $gestion->monto_pagado;
        if ($monto > $saldoGestion) {
            throw ValidationException::withMessages([
                'monto' => "El monto no puede exceder \${$saldoGestion} pendiente de esta cuota.",
            ]);
        }

        // Registrar pago
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

        // Actualizar gestión
        $nuevoMontoPagado = $gestion->monto_pagado + $monto;
        $estado = $nuevoMontoPagado >= $gestion->monto_cuota ? 'cobrado' : 'parcialmente_cobrado';
        $gestion->update(['monto_pagado' => $nuevoMontoPagado, 'estado' => $estado]);

        // Actualizar venta
        $venta = $gestion->venta;
        $venta->monto_pagado += $monto;
        $venta->saldo_pendiente = max(0, $venta->saldo_pendiente - $monto);
        if ($venta->saldo_pendiente == 0) {
            $venta->estado = 'completada';
        }
        $venta->save();

        return response()->json([
            'mensaje' => $estado === 'cobrado' ? 'Cuota pagada completamente.' : 'Pago parcial registrado.',
            'gestion' => [
                'id' => $gestion->id,
                'cuota' => "{$gestion->numero_cuota}/{$gestion->total_cuotas}",
                'monto_cuota' => $gestion->monto_cuota,
                'monto_pagado' => $nuevoMontoPagado,
                'saldo_pendiente' => round($gestion->monto_cuota - $nuevoMontoPagado, 2),
                'estado' => $estado,
            ],
            'venta_saldo' => $venta->saldo_pendiente,
        ]);
    }
}
