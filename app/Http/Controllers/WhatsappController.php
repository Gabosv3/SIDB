<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\GestionCobro;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    public function __construct(protected WhatsAppCloudApiService $whatsapp) {}

    /**
     * Bandeja principal de conversaciones
     */
    public function index(Request $request, $tenant)
    {
        $filtro = $request->get('filtro', 'todos');
        $buscar = $request->get('q', '');

        $query = WhatsappConversation::with([
            'cliente:id,nombre,apellido,telefono_whatsapp,ruta_cobro_id',
            'ultimoMensaje',
        ])->orderByDesc('ultimo_mensaje_at');

        // Búsqueda por nombre de cliente
        if ($buscar) {
            $query->whereHas('cliente', function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apellido', 'like', "%{$buscar}%")
                  ->orWhere('telefono_whatsapp', 'like', "%{$buscar}%");
            });
        }

        if ($filtro === 'abiertas') {
            $query->where('estado', 'abierta');
        } elseif ($filtro === 'cerradas') {
            $query->where('estado', 'cerrada');
        } elseif ($filtro === 'sin_respuesta') {
            // Último mensaje fue saliente (out), nadie ha respondido
            $query->whereHas('ultimoMensaje', fn ($q) => $q->where('direction', 'out'));
        }

        $conversaciones = $query->paginate(30);

        // ── Estadísticas ──────────────────────────────────────────────────────
        $hace30Dias = \Carbon\Carbon::now()->subDays(30);

        $stats = [
            'conversaciones_activas' => WhatsappConversation::where('estado', 'abierta')->count(),
            'recordatorios_enviados' => WhatsappMessage::where('direction', 'out')
                ->where('created_at', '>=', $hace30Dias)
                ->count(),
            'cubiertas_confirmadas' => \App\Models\GestionCobro::where('estado', 'pagada')
                ->where('updated_at', '>=', $hace30Dias)
                ->count(),
            'respuestas_pendientes' => WhatsappConversation::whereHas(
                'ultimoMensaje',
                fn ($q) => $q->where('direction', 'out')
            )->count(),
        ];

        // Conversación activa si viene con ?c=ID
        $conversacionActiva = null;
        if ($request->has('c')) {
            $conversacionActiva = WhatsappConversation::with([
                'cliente',
                'mensajes',
            ])->find($request->c);
        }

        // Cuentas configuradas
        $cuentas = WhatsappAccount::where('estado', 'activo')->count();

        return view('whatsapp.index', compact(
            'conversaciones', 'conversacionActiva', 'filtro',
            'tenant', 'stats', 'buscar', 'cuentas'
        ));
    }

    /**
     * Cargar mensajes de una conversación (AJAX)
     */
    public function show($tenant, WhatsappConversation $conversation): JsonResponse
    {
        $conversation->load(['cliente:id,nombre,apellido,telefono_whatsapp', 'mensajes']);

        // Cuotas pendientes del cliente
        $cuotasPendientes = GestionCobro::where('cliente_id', $conversation->cliente_id)
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha_vencimiento')
            ->get(['id', 'numero_cuota', 'total_cuotas', 'monto_cuota', 'fecha_vencimiento', 'estado']);

        return response()->json([
            'conversation'    => $conversation,
            'cuotas_pendientes' => $cuotasPendientes,
        ]);
    }

    /**
     * Enviar un mensaje al cliente
     */
    public function send(Request $request, $tenant): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|exists:whatsapp_conversations,id',
            'message'         => 'required|string|max:4096',
        ]);

        $conversation = WhatsappConversation::with('cliente')->findOrFail($request->conversation_id);
        $numero = $conversation->wa_id ?? $conversation->cliente?->telefono_whatsapp;

        if (! $numero) {
            return response()->json(['error' => 'El cliente no tiene número de WhatsApp registrado.'], 422);
        }

        // Resolver cuenta de WhatsApp para este cliente
        $cuenta = $conversation->cliente
            ? WhatsappAccount::resolverParaCliente($conversation->cliente)
            : WhatsappAccount::where('is_default', true)->where('estado', 'activo')->first();

        // Guardar mensaje como pendiente
        $mensaje = WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'out',
            'type'            => 'text',
            'body'            => $request->message,
            'status'          => 'pending',
            'sent_at'         => now(),
        ]);

        // Enviar a Meta Cloud API
        $resultado = $this->whatsapp->sendTextMessage($cuenta, $numero, $request->message);

        // Actualizar estado según resultado
        $mensaje->update([
            'status'       => $resultado['success'] ? 'sent' : 'failed',
            'wa_message_id'=> $resultado['message_id'] ?? null,
        ]);

        // Actualizar resumen de conversación
        $conversation->update([
            'ultimo_mensaje'    => $request->message,
            'ultimo_mensaje_at' => now(),
            'estado'            => 'abierta',
        ]);

        return response()->json([
            'success'   => $resultado['success'],
            'simulated' => $resultado['simulated'] ?? false,
            'mensaje'   => $mensaje,
        ]);
    }

    /**
     * Enviar recordatorio de cuota rápido
     */
    public function sendReminder(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $numero = $cliente->telefono_whatsapp;

        if (! $numero) {
            return response()->json(['error' => 'El cliente no tiene número de WhatsApp registrado.'], 422);
        }

        // Obtener próxima cuota pendiente
        $cuota = GestionCobro::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha_vencimiento')
            ->first();

        $texto = $cuota
            ? "Hola {$cliente->nombre}, le recordamos que tiene una cuota pendiente de \${$cuota->monto_cuota} con vencimiento el {$cuota->fecha_vencimiento->format('d/m/Y')}. Por favor comuníquese con nosotros. Distribuidora Briancesco Menjivar."
            : "Hola {$cliente->nombre}, le contactamos de parte de Distribuidora Briancesco Menjivar. Por favor comuníquese con nosotros.";

        $conversation = $this->crearOAbrirConversacion($cliente);

        // Resolver cuenta de WhatsApp para el cliente
        $cuenta = WhatsappAccount::resolverParaCliente($cliente);

        $mensaje = WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'out',
            'type'            => 'text',
            'body'            => $texto,
            'status'          => 'pending',
            'sent_at'         => now(),
        ]);

        $resultado = $this->whatsapp->sendTextMessage($cuenta, $numero, $texto);

        $mensaje->update([
            'status'        => $resultado['success'] ? 'sent' : 'failed',
            'wa_message_id' => $resultado['message_id'] ?? null,
        ]);

        $conversation->update([
            'ultimo_mensaje'    => $texto,
            'ultimo_mensaje_at' => now(),
        ]);

        return response()->json([
            'success'          => $resultado['success'],
            'simulated'        => $resultado['simulated'] ?? false,
            'conversation_id'  => $conversation->id,
        ]);
    }

    /**
     * Crear o abrir conversación existente de un cliente
     */
    public function openClient(Request $request, $tenant, Cliente $cliente)
    {
        $conversation = $this->crearOAbrirConversacion($cliente);

        // Si es AJAX, devolver JSON
        if ($request->expectsJson()) {
            return response()->json(['conversation_id' => $conversation->id]);
        }

        // Si es web, redirigir a la bandeja con la conversación activa
        return redirect()->route('whatsapp.index', ['tenant' => $tenant, 'c' => $conversation->id]);
    }

    /**
     * Generar texto rápido con variables del cliente
     */
    public function quickMessage(Request $request, $tenant): JsonResponse
    {
        $request->validate([
            'tipo'            => 'required|in:recordar_cuota,saldo_pendiente,confirmar_pago,promesa_pago',
            'conversation_id' => 'required|exists:whatsapp_conversations,id',
        ]);

        $conv    = WhatsappConversation::with(['cliente.gestionesCobro' => fn($q) => $q->whereIn('estado', ['pendiente','vencida'])->orderBy('fecha_vencimiento')])->findOrFail($request->conversation_id);
        $cliente = $conv->cliente;
        $cuota   = $cliente?->gestionesCobro->first();

        $textos = [
            'recordar_cuota'  => "Hola {$cliente?->nombre}, le recordamos que tiene una cuota pendiente por $" . number_format($cuota?->monto_cuota ?? 0, 2) . " con vencimiento el " . ($cuota?->fecha_vencimiento?->format('d/m/Y') ?? 'N/A') . ". ¿Podría confirmarnos cuándo realizará el pago? Gracias.",
            'saldo_pendiente' => "Hola {$cliente?->nombre}, su saldo pendiente actual es de $" . number_format($cliente?->saldo ?? 0, 2) . ". Si ya realizó el pago, por favor envíenos el comprobante. Distribuidora Briancesco Menjivar.",
            'confirmar_pago'  => "Hola {$cliente?->nombre}, confirmamos que hemos recibido su pago. ¡Gracias por estar al día con nosotros! Distribuidora Briancesco Menjivar.",
            'promesa_pago'    => "Hola {$cliente?->nombre}, hemos registrado su promesa de pago. Le estaremos contactando en la fecha acordada. Distribuidora Briancesco Menjivar.",
        ];

        return response()->json([
            'texto' => $textos[$request->tipo] ?? '',
        ]);
    }

    // ── Helper interno ─────────────────────────────────────────────────────────

    private function crearOAbrirConversacion(Cliente $cliente): WhatsappConversation
    {
        return WhatsappConversation::firstOrCreate(
            ['cliente_id' => $cliente->id],
            [
                'user_id'        => auth()->id(),
                'wa_id'          => $cliente->telefono_whatsapp,
                'estado'         => 'abierta',
            ]
        );
    }
}
