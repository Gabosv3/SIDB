<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BaileysWebhookController extends Controller
{
    /**
     * Recibir webhook de Baileys cuando llega un mensaje
     */
    public function receive(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'from' => 'required|string',
                'message' => 'required|string',
                'timestamp' => 'required|integer',
                'messageId' => 'required|string',
            ]);

            $from = $request->input('from');
            $messageText = $request->input('message');
            $timestamp = $request->input('timestamp');
            $messageId = $request->input('messageId');

            // Extraer número sin @s.whatsapp.net
            $phoneNumber = str_replace('@s.whatsapp.net', '', $from);
            $phoneNumber = str_replace('@g.us', '', $phoneNumber); // Grupos

            Log::info('[Baileys Webhook] Mensaje recibido', [
                'from' => $from,
                'message' => $messageText,
                'phone' => $phoneNumber,
            ]);

            // Buscar o crear cliente por teléfono WhatsApp
            $cliente = Cliente::where('telefono_whatsapp', $phoneNumber)->first();

            if (!$cliente) {
                Log::warning("[Baileys Webhook] Cliente no encontrado: {$phoneNumber}");
                return response()->json(['error' => 'Cliente no encontrado'], 404);
            }

            // Buscar o crear conversación
            $conversacion = WhatsappConversation::firstOrCreate(
                ['cliente_id' => $cliente->id],
                [
                    'wa_id' => $from,
                    'estado' => 'abierta',
                    'fuente' => 'baileys', // Marcar que es de Baileys, no de Meta
                ]
            );

            // Guardar mensaje
            $msg = WhatsappMessage::create([
                'conversation_id' => $conversacion->id,
                'wa_message_id' => $messageId,
                'direction' => 'in',
                'type' => 'text',
                'body' => $messageText,
                'status' => 'received',
                'received_at' => now(),
            ]);

            // Actualizar último mensaje
            $conversacion->update([
                'ultimo_mensaje' => $messageText,
                'ultimo_mensaje_at' => now(),
            ]);

            Log::info('[Baileys Webhook] Mensaje guardado', [
                'cliente_id' => $cliente->id,
                'conversation_id' => $conversacion->id,
                'message_id' => $msg->id,
            ]);

            return response()->json([
                'success' => true,
                'message_id' => $msg->id,
                'cliente_id' => $cliente->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[Baileys Webhook] Error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Status del servidor Baileys (para monitoreo)
     */
    public function status(): JsonResponse
    {
        $baileys = app(BaileysWhatsAppService::class);
        $status = $baileys->getStatus();

        return response()->json($status);
    }

    /**
     * Obtener código QR
     */
    public function qrcode(): JsonResponse
    {
        $baileys = app(BaileysWhatsAppService::class);
        $qr = $baileys->getQRCode();

        if (!$qr) {
            return response()->json(['error' => 'QR no disponible'], 404);
        }

        return response()->json(['qr' => $qr]);
    }

    /**
     * Información de la cuenta conectada
     */
    public function info(): JsonResponse
    {
        $baileys = app(BaileysWhatsAppService::class);
        $info = $baileys->getInfo();

        return response()->json($info);
    }
}
