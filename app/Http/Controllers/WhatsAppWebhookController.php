<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verificación del webhook (Meta llama esto al configurar)
     * GET /whatsapp/webhook
     */
    public function verify(Request $request): Response
    {
        $verifyToken = config('services.whatsapp.webhook_verify_token', 'mi_token_secreto');
        $mode        = $request->query('hub_mode');
        $token       = $request->query('hub_verify_token');
        $challenge   = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Recibir mensajes entrantes y actualizaciones de estado
     * POST /whatsapp/webhook
     */
    public function receive(Request $request): Response
    {
        $payload = $request->all();
        Log::info('[WhatsApp Webhook] Payload recibido', $payload);

        try {
            $entry = $payload['entry'][0] ?? null;
            if (! $entry) {
                return response('ok', 200);
            }

            $changes = $entry['changes'][0] ?? null;
            if (! $changes) {
                return response('ok', 200);
            }

            $value = $changes['value'] ?? [];

            // ── Mensajes entrantes ─────────────────────────────────────────────
            if (! empty($value['messages'])) {
                foreach ($value['messages'] as $msg) {
                    $this->procesarMensajeEntrante($msg, $value);
                }
            }

            // ── Actualizaciones de estado (sent/delivered/read/failed) ─────────
            if (! empty($value['statuses'])) {
                foreach ($value['statuses'] as $status) {
                    $this->procesarActualizacionEstado($status);
                }
            }

        } catch (\Exception $e) {
            Log::error('[WhatsApp Webhook] Error procesando payload: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Siempre responder 200 a Meta para no reintentos
        return response('ok', 200);
    }

    // ── Privados ───────────────────────────────────────────────────────────────

    private function procesarMensajeEntrante(array $msg, array $value): void
    {
        $from      = $msg['from'] ?? null; // Número del cliente
        $messageId = $msg['id'] ?? null;
        $type      = $msg['type'] ?? 'text';
        $body      = $msg['text']['body'] ?? null;
        $timestamp = $msg['timestamp'] ?? null;

        if (! $from) return;

        // Buscar cliente por telefono_whatsapp
        $cliente = Cliente::where('telefono_whatsapp', $from)
            ->orWhere('telefono_whatsapp', '+' . $from)
            ->first();

        // Buscar o crear conversación
        $conversation = WhatsappConversation::firstOrCreate(
            ['wa_id' => $from],
            [
                'cliente_id'      => $cliente?->id,
                'estado'          => 'abierta',
                'phone_number_id' => $value['metadata']['phone_number_id'] ?? null,
            ]
        );

        // Si el cliente se encontró después, actualizar
        if ($cliente && ! $conversation->cliente_id) {
            $conversation->update(['cliente_id' => $cliente->id]);
        }

        // Guardar mensaje entrante
        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'wa_message_id'   => $messageId,
            'direction'       => 'in',
            'type'            => $type,
            'body'            => $body,
            'status'          => 'read',
            'payload'         => $msg,
            'received_at'     => $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : now(),
        ]);

        // Actualizar resumen de conversación
        $conversation->update([
            'ultimo_mensaje'    => $body ?? "[{$type}]",
            'ultimo_mensaje_at' => now(),
            'estado'            => 'abierta',
        ]);
    }

    private function procesarActualizacionEstado(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $estado    = $status['status'] ?? null; // sent, delivered, read, failed

        if (! $messageId || ! $estado) return;

        WhatsappMessage::where('wa_message_id', $messageId)
            ->update(['status' => $estado]);
    }
}
