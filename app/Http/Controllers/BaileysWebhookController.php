<?php

namespace App\Http\Controllers;

use App\Models\BaileysSession;
use App\Models\Cliente;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\BaileysWhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BaileysWebhookController extends Controller
{
    public function __construct(protected BaileysWhatsAppService $baileys) {}

    /**
     * Recibir webhook de Baileys cuando llega un mensaje
     */
    public function receive(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sessionId' => 'nullable|string',
                'from' => 'required|string',
                'message' => 'required|string',
                'timestamp' => 'required|integer',
                'messageId' => 'required|string',
            ]);

            $sessionId = $request->input('sessionId');
            $from = $request->input('from');
            $messageText = $request->input('message');
            $messageId = $request->input('messageId');

            // Extraer número sin @s.whatsapp.net
            $phoneNumber = str_replace('@s.whatsapp.net', '', $from);
            $phoneNumber = str_replace('@g.us', '', $phoneNumber); // Grupos

            Log::info('[Baileys Webhook] Mensaje recibido', [
                'sessionId' => $sessionId,
                'from' => $from,
                'message' => $messageText,
                'phone' => $phoneNumber,
            ]);

            // Buscar cliente por teléfono WhatsApp
            $cliente = Cliente::where('telefono_whatsapp', $phoneNumber)->first();

            if (!$cliente) {
                Log::warning("[Baileys Webhook] Cliente no encontrado: {$phoneNumber}");
                return response()->json(['error' => 'Cliente no encontrado'], 404);
            }

            // Resolver a qué empleado pertenece esta sesión, para que la conversación
            // quede en el hilo del empleado correcto (una por par cliente+empleado)
            $userId = $sessionId
                ? BaileysSession::where('session_key', $sessionId)->value('user_id')
                : null;

            $conversacion = WhatsappConversation::firstOrCreate(
                ['cliente_id' => $cliente->id, 'user_id' => $userId],
                ['wa_id' => $from, 'estado' => 'abierta']
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
                'estado' => 'abierta',
            ]);

            if ($userId) {
                BaileysSession::where('user_id', $userId)->update(['ultima_actividad_at' => now()]);
            }

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
     * Iniciar la sesión de Baileys del usuario autenticado (app móvil)
     */
    public function connect(Request $request): JsonResponse
    {
        $sessionKey = BaileysSession::sessionKeyPara($request->user()->id);
        $resultado = $this->baileys->connect($sessionKey);

        BaileysSession::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['session_key' => $sessionKey]
        );

        return response()->json($resultado);
    }

    /**
     * Status de la sesión de Baileys del usuario autenticado (app móvil)
     */
    public function status(Request $request): JsonResponse
    {
        $sessionKey = BaileysSession::sessionKeyPara($request->user()->id);
        $status = $this->baileys->getStatus($sessionKey);

        return response()->json($status);
    }

    /**
     * Obtener código QR de la sesión del usuario autenticado (app móvil)
     */
    public function qrcode(Request $request): JsonResponse
    {
        $sessionKey = BaileysSession::sessionKeyPara($request->user()->id);
        $qr = $this->baileys->getQRCode($sessionKey);

        if (!$qr) {
            return response()->json(['error' => 'QR no disponible'], 404);
        }

        return response()->json(['qr' => $qr]);
    }

    /**
     * Información de la cuenta conectada en la sesión del usuario autenticado (app móvil)
     */
    public function info(Request $request): JsonResponse
    {
        $sessionKey = BaileysSession::sessionKeyPara($request->user()->id);
        $info = $this->baileys->getInfo($sessionKey);

        return response()->json($info);
    }
}
