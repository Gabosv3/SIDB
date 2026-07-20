<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vale;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ValeController extends Controller
{
    #[OA\Get(
        path: '/vales',
        summary: 'Listar los vales propios',
        description: 'Historial de vales (consumo y vehículo) enviados por el usuario autenticado, del más reciente al más antiguo.',
        security: [['sanctum' => []]],
        tags: ['Vales'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pendiente', 'aprobado', 'rechazado'])),
            new OA\Parameter(name: 'tipo', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['consumo', 'vehiculo'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de vales'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Vale::where('user_id', $request->user()->id)
            ->with('vehiculo:id,placa,tipo')
            ->latest();

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo'));
        }

        $vales = $query->get()->map(fn (Vale $v) => [
            'id'                     => $v->id,
            'tipo'                   => $v->tipo,
            'vehiculo'               => $v->vehiculo?->placa,
            'categoria_vehiculo'     => $v->categoria_vehiculo,
            'monto'                  => (float) $v->monto,
            'comprobante_url'        => $v->comprobante_url,
            'descripcion'            => $v->descripcion,
            'fecha_gasto'            => $v->fecha_gasto->toDateString(),
            'estado'                 => $v->estado,
            'observaciones_admin'    => $v->observaciones_admin,
            'descuenta_cobro_diario' => $v->descuenta_cobro_diario,
            'creado'                 => $v->created_at->format('d/m/Y H:i'),
        ]);

        return response()->json($vales);
    }

    #[OA\Post(
        path: '/vales',
        summary: 'Enviar un vale (consumo o vehículo)',
        description: 'Vale de consumo: solo requiere monto y comprobante. Vale de vehículo: requiere además vehiculo_id y categoria_vehiculo (gasolina o imprevisto — mantenimiento es de uso administrativo). Queda en estado "pendiente" hasta que un administrador lo apruebe o rechace.',
        security: [['sanctum' => []]],
        tags: ['Vales'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['tipo', 'monto', 'comprobante'],
                    properties: [
                        new OA\Property(property: 'tipo', type: 'string', enum: ['consumo', 'vehiculo']),
                        new OA\Property(property: 'monto', type: 'number', format: 'float', example: 5.00),
                        new OA\Property(property: 'comprobante', type: 'string', format: 'binary', description: 'Foto del comprobante (jpg/png, max 5MB)'),
                        new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                        new OA\Property(property: 'fecha_gasto', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'vehiculo_id', type: 'integer', nullable: true, description: 'Requerido si tipo=vehiculo'),
                        new OA\Property(property: 'categoria_vehiculo', type: 'string', enum: ['gasolina', 'imprevisto'], nullable: true, description: 'Requerido si tipo=vehiculo'),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Vale enviado'),
            new OA\Response(response: 403, description: 'El vehículo no te pertenece ni es de reserva'),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo'               => 'required|in:consumo,vehiculo',
            'monto'              => 'required|numeric|min:0.01',
            'comprobante'        => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'descripcion'        => 'nullable|string|max:500',
            'fecha_gasto'        => 'nullable|date',
            'vehiculo_id'        => 'required_if:tipo,vehiculo|nullable|integer|exists:vehiculos,id',
            // "mantenimiento" es de uso administrativo (Filament), no se acepta desde el móvil.
            'categoria_vehiculo' => 'required_if:tipo,vehiculo|nullable|in:gasolina,imprevisto',
        ]);

        $user = $request->user();

        if ($data['tipo'] === 'vehiculo') {
            $vehiculo = Vehiculo::find($data['vehiculo_id']);
            $esSuyo = $vehiculo && ($vehiculo->asignado_a === $user->id || $vehiculo->estado === 'reserva');

            if (! $esSuyo) {
                return response()->json(['mensaje' => 'Este vehículo no te pertenece ni está disponible como reserva.'], 403);
            }
        }

        $sucursalId = $user->vendedor?->sucursal_id ?? $user->cobrador?->sucursal_id;

        $comprobantePath = $request->file('comprobante')->store("vales/{$user->id}", 'public');

        $vale = Vale::create([
            'user_id'                => $user->id,
            'sucursal_id'            => $sucursalId,
            'tipo'                   => $data['tipo'],
            'vehiculo_id'            => $data['vehiculo_id'] ?? null,
            'categoria_vehiculo'     => $data['categoria_vehiculo'] ?? null,
            'monto'                  => $data['monto'],
            'comprobante'            => $comprobantePath,
            'descripcion'            => $data['descripcion'] ?? null,
            'fecha_gasto'            => $data['fecha_gasto'] ?? now()->toDateString(),
            'estado'                 => 'pendiente',
            // Todo lo enviado desde el móvil es plata que el empleado ya pagó de
            // lo cobrado ese día (imprevisto de calle, gasolina, consumo) — sí se
            // descuenta del efectivo a entregar en Resumen del Día.
            'descuenta_cobro_diario' => true,
        ]);

        return response()->json([
            'mensaje' => 'Vale enviado, queda pendiente de aprobación.',
            'vale'    => [
                'id'              => $vale->id,
                'tipo'            => $vale->tipo,
                'monto'           => (float) $vale->monto,
                'comprobante_url' => Storage::url($comprobantePath),
                'estado'          => $vale->estado,
            ],
        ], 201);
    }
}
