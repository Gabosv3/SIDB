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
     * Iniciar (o reanudar) la sesión de un empleado — dispara el flujo de QR si no existe aún.
     */
    public function connect(string $sessionId): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/sessions/{$sessionId}/connect");
            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('[Baileys] Error conectando sesión: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener estado de conexión de la sesión de un empleado
     */
    public function getStatus(string $sessionId): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/sessions/{$sessionId}/status");
            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('[Baileys] Error obteniendo status: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Enviar mensaje de texto desde la sesión de un empleado
     */
    public function sendMessage(string $sessionId, string $to, string $message): array
    {
        try {
            // Formato: 573001234567 -> 573001234567@s.whatsapp.net
            $jid = str_contains($to, '@') ? $to : "{$to}@s.whatsapp.net";

            $response = Http::post("{$this->baseUrl}/sessions/{$sessionId}/send", [
                'to' => $jid,
                'message' => $message,
            ]);

            if ($response->failed()) {
                Log::error('[Baileys] Error enviando mensaje', [
                    'sessionId' => $sessionId,
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
                'sessionId' => $sessionId,
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
     * Enviar mensaje usando plantilla, desde la sesión de un empleado
     */
    public function sendTemplate(string $sessionId, string $to, string $template, array $params = []): array
    {
        try {
            $jid = str_contains($to, '@') ? $to : "{$to}@s.whatsapp.net";

            $response = Http::post("{$this->baseUrl}/sessions/{$sessionId}/send-template", [
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
     * Obtener información de la cuenta conectada en la sesión de un empleado
     */
    public function getInfo(string $sessionId): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/sessions/{$sessionId}/info");
            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('[Baileys] Error obteniendo info: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtener código QR de la sesión de un empleado
     */
    public function getQRCode(string $sessionId): ?string
    {
        try {
            $response = Http::get("{$this->baseUrl}/sessions/{$sessionId}/qrcode");

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
     * Desconectar la sesión de un empleado
     */
    public function disconnect(string $sessionId): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/sessions/{$sessionId}/disconnect");
            return $response->json() ?? [];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reconectar (generar nuevo QR) la sesión de un empleado
     */
    public function reconnect(string $sessionId): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/sessions/{$sessionId}/reconnect");
            return $response->json() ?? [];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verificar si el servidor Baileys está disponible (independiente de sesiones)
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->baseUrl}/sessions/_ping/status");
            return $response->ok();
        } catch (\Exception $e) {
            return false;
        }
    }
}
