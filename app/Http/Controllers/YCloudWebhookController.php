<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recibe los eventos de WhatsApp Coexistence desde YCloud (BSP oficial de
 * Meta). Por ahora solo procesa mensajes entrantes de clientes
 * (whatsapp.inbound_message.received) y los guarda en whatsapp_conversations
 * / whatsapp_messages, que ya alimentan el WhatsApp Monitor de solo lectura.
 */
class YCloudWebhookController extends Controller
{
    public function receive(Request $request): Response
    {
        if (! $this->firmaValida($request)) {
            Log::warning('YCloud webhook: firma inválida', ['ip' => $request->ip()]);

            return response('firma inválida', 401);
        }

        $payload = $request->json()->all();

        if (($payload['type'] ?? null) !== 'whatsapp.inbound_message.received') {
            // Otros eventos (delivery, read, status de plantillas, etc.) se
            // reconocen pero no se procesan todavía — solo se aceptan para
            // que YCloud no reintente el envío.
            return response('ok', 200);
        }

        $mensaje = $payload['whatsappInboundMessage'] ?? null;

        if (! $mensaje || empty($mensaje['from'])) {
            return response('ok', 200);
        }

        $waId = preg_replace('/\D/', '', $mensaje['from']);
        $cliente = $this->buscarClientePorTelefono($waId);

        $conversacion = WhatsappConversation::firstOrCreate(
            ['wa_id' => $waId],
            ['cliente_id' => $cliente?->id, 'estado' => 'abierta']
        );

        // Si la conversación ya existía sin cliente vinculado y ahora sí lo
        // encontramos (p. ej. se acaba de registrar), la enlazamos.
        if ($cliente && $conversacion->cliente_id !== $cliente->id) {
            $conversacion->cliente_id = $cliente->id;
        }

        $cuerpo = $mensaje['text']['body'] ?? match ($mensaje['type'] ?? null) {
            'image' => '[Imagen]',
            'document' => '[Documento]',
            'audio' => '[Audio]',
            default => '[Mensaje]',
        };

        $enviadoEn = isset($mensaje['sendTime']) ? \Carbon\Carbon::parse($mensaje['sendTime']) : now();

        WhatsappMessage::create([
            'conversation_id' => $conversacion->id,
            'wa_message_id' => $mensaje['wamid'] ?? null,
            'direction' => 'in',
            'type' => in_array($mensaje['type'] ?? 'text', ['text', 'image', 'document', 'audio'], true)
                ? $mensaje['type']
                : 'text',
            'body' => $cuerpo,
            'status' => 'delivered',
            'payload' => $mensaje,
            'received_at' => $enviadoEn,
        ]);

        $conversacion->ultimo_mensaje = $cuerpo;
        $conversacion->ultimo_mensaje_at = $enviadoEn;
        $conversacion->estado = 'abierta';
        $conversacion->save();

        return response('ok', 200);
    }

    private function firmaValida(Request $request): bool
    {
        $secret = config('services.ycloud.webhook_secret');

        if (! $secret) {
            // Sin secret configurado no se puede verificar — se rechaza para
            // no aceptar webhooks falsificados por error de configuración.
            Log::error('YCloud webhook: falta YCLOUD_WEBHOOK_SECRET en .env');

            return false;
        }

        $header = $request->header('YCloud-Signature', '');

        if (! preg_match('/t=(\d+),s=([a-f0-9]+)/', $header, $m)) {
            return false;
        }

        [$_, $timestamp, $firmaRecibida] = $m;

        $firmaEsperada = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        return hash_equals($firmaEsperada, $firmaRecibida);
    }

    private function buscarClientePorTelefono(string $waId): ?Cliente
    {
        // wa_id viene en formato internacional sin '+' (ej. 50370000000).
        // Los teléfonos guardados en clientes son locales de 8 dígitos pero
        // con guion (ej. "7000-0000"), así que se le quita todo lo que no
        // sea dígito antes de comparar contra los últimos 8 dígitos del wa_id.
        $ultimosDigitos = substr($waId, -8);

        return Cliente::whereRaw(
            "REPLACE(telefono_whatsapp, '-', '') LIKE ? OR REPLACE(telefono_normal, '-', '') LIKE ?",
            ["%{$ultimosDigitos}", "%{$ultimosDigitos}"]
        )->first();
    }
}
