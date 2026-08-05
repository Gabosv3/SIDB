<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Webhook directo de Meta Cloud API (WhatsApp Coexistence, Embedded Signup
 * propio — sin BSP intermediario como YCloud). Reemplaza a
 * YCloudWebhookController para los números conectados por esta vía.
 *
 * Meta llama a esta misma URL con dos métodos distintos:
 * - GET: verificación inicial del webhook (una sola vez, al configurarlo).
 * - POST: eventos reales (mensajes entrantes, cambios de estado, etc.).
 */
class MetaWhatsAppWebhookController extends Controller
{
    public function verificar(Request $request): Response
    {
        $modo = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($modo === 'subscribe' && $token === config('services.meta_whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        Log::warning('Meta WhatsApp webhook: verificación fallida', ['token_recibido' => $token]);

        return response('token inválido', 403);
    }

    public function recibir(Request $request): Response
    {
        $payload = $request->json()->all();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $valor = $change['value'] ?? [];

                foreach ($valor['messages'] ?? [] as $mensaje) {
                    $this->procesarMensaje($mensaje, $valor['contacts'] ?? []);
                }
            }
        }

        return response('ok', 200);
    }

    private function procesarMensaje(array $mensaje, array $contactos): void
    {
        $waId = preg_replace('/\D/', '', $mensaje['from'] ?? '');

        if ($waId === '') {
            return;
        }

        $cliente = $this->buscarClientePorTelefono($waId);

        $conversacion = WhatsappConversation::firstOrCreate(
            ['wa_id' => $waId],
            ['cliente_id' => $cliente?->id, 'estado' => 'abierta']
        );

        if ($cliente && $conversacion->cliente_id !== $cliente->id) {
            $conversacion->cliente_id = $cliente->id;
        }

        $tipo = $mensaje['type'] ?? 'text';
        $cuerpo = match ($tipo) {
            'text' => $mensaje['text']['body'] ?? '',
            'image' => '[Imagen]',
            'document' => '[Documento]',
            'audio' => '[Audio]',
            default => '[Mensaje]',
        };

        $enviadoEn = isset($mensaje['timestamp']) ? \Carbon\Carbon::createFromTimestamp((int) $mensaje['timestamp']) : now();

        WhatsappMessage::create([
            'conversation_id' => $conversacion->id,
            'wa_message_id' => $mensaje['id'] ?? null,
            'direction' => 'in',
            'type' => in_array($tipo, ['text', 'image', 'document', 'audio'], true) ? $tipo : 'text',
            'body' => $cuerpo,
            'status' => 'delivered',
            'payload' => $mensaje,
            'received_at' => $enviadoEn,
        ]);

        $conversacion->ultimo_mensaje = $cuerpo;
        $conversacion->ultimo_mensaje_at = $enviadoEn;
        $conversacion->estado = 'abierta';
        $conversacion->save();
    }

    private function buscarClientePorTelefono(string $waId): ?Cliente
    {
        $ultimosDigitos = substr($waId, -8);

        return Cliente::whereRaw(
            "REPLACE(telefono_whatsapp, '-', '') LIKE ? OR REPLACE(telefono_normal, '-', '') LIKE ?",
            ["%{$ultimosDigitos}", "%{$ultimosDigitos}"]
        )->first();
    }
}
