<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaileysWhatsAppService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.baileys.url') ?? 'http://localhost:3333';
        $this->token = config('services.baileys.token') ?? '';
    }

    /**
     * Obtener estado de conexión
     */
    public function getStatus(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/status");
            return $response->json();
        } catch (\Exception $e) {
            Log::error('[Baileys] Error obteniendo status: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Enviar mensaje de texto
     */
    public function sendMessage(string $to, string $message): array
    {
        try {
            // Formato: 573001234567 -> 573001234567@s.whatsapp.net
            $jid = str_contains($to, '@') ? $to : "{$to}@s.whatsapp.net";

            $response = Http::post("{$this->baseUrl}/send", [
                'to' => $jid,
                'message' => $message,
            ]);

            if ($response->failed()) {
                Log::error('[Baileys] Error enviando mensaje', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => $response->json('error', 'Error desconocido'),
                ];
            }

            Log::info('[Baileys] Mensaje enviado', [
                'to' => $to,
                'messageId' => $response->json('messageId'),
            ]);

            return [
                'success' => true,
                'messageId' => $response->json('messageId'),
                'timestamp' => $response->json('timestamp'),
            ];

        } catch (\Exception $e) {
            Log::error('[Baileys] Excepción enviando mensaje: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar mensaje usando plantilla
     */
    public function sendTemplate(string $to, string $template, array $params = []): array
    {
        try {
            $jid = str_contains($to, '@') ? $to : "{$to}@s.whatsapp.net";

            $response = Http::post("{$this->baseUrl}/send-template", [
                'to' => $jid,
                'template' => $template,
                'params' => $params,
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => $response->json('error', 'Error desconocido'),
                ];
            }

            return [
                'success' => true,
                'messageId' => $response->json('messageId'),
                'message' => $response->json('message'),
            ];

        } catch (\Exception $e) {
            Log::error('[Baileys] Error enviando plantilla: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener información de la cuenta
     */
    public function getInfo(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/info");
            return $response->json();
        } catch (\Exception $e) {
            Log::error('[Baileys] Error obteniendo info: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtener código QR
     */
    public function getQRCode(): ?string
    {
        try {
            $response = Http::get("{$this->baseUrl}/qrcode");
            
            if ($response->failed()) {
                return null;
            }

            return $response->json('qr');
        } catch (\Exception $e) {
            Log::error('[Baileys] Error obteniendo QR: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Desconectar WhatsApp
     */
    public function disconnect(): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/disconnect");
            return $response->json();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reconectar (generar nuevo QR)
     */
    public function reconnect(): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/reconnect");
            return $response->json();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verificar si Baileys está disponible
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->baseUrl}/status");
            return $response->ok();
        } catch (\Exception $e) {
            return false;
        }
    }
}
