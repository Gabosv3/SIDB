<?php

namespace App\Services;

use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudApiService
{
    protected string $apiUrl = 'https://graph.facebook.com/v19.0';

    // ── Enviar mensaje de texto ───────────────────────────────────────────────

    public function sendTextMessage(WhatsappAccount|null $account, string $to, string $message): array
    {
        $to = $this->formatPhoneNumber($to);

        if (! $account || ! $account->estaConfigurado()) {
            Log::info("[WhatsApp SIMULADO] Mensaje a {$to}: {$message}");
            return [
                'success'    => true,
                'simulated'  => true,
                'message_id' => 'SIM_' . uniqid(),
            ];
        }

        try {
            $token         = $account->access_token_decrypted;
            $phoneNumberId = $account->phone_number_id;

            $response = Http::withToken($token)
                ->post("{$this->apiUrl}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => [
                        'preview_url' => false,
                        'body'        => $message,
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'success'    => true,
                    'simulated'  => false,
                    'message_id' => $response->json('messages.0.id'),
                ];
            }

            Log::error('[WhatsApp API] Error enviando mensaje', [
                'to'       => $to,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error'   => $response->json('error.message', 'Error desconocido'),
            ];

        } catch (\Exception $e) {
            Log::error('[WhatsApp API] Excepción: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Enviar plantilla ──────────────────────────────────────────────────────

    public function sendTemplateMessage(
        WhatsappAccount|null $account,
        string $to,
        string $templateName,
        string $language = 'es',
        array $parameters = []
    ): array {
        $to = $this->formatPhoneNumber($to);

        if (! $account || ! $account->estaConfigurado()) {
            Log::info("[WhatsApp SIMULADO] Template '{$templateName}' a {$to}");
            return [
                'success'    => true,
                'simulated'  => true,
                'message_id' => 'SIM_TMPL_' . uniqid(),
            ];
        }

        try {
            $token         = $account->access_token_decrypted;
            $phoneNumberId = $account->phone_number_id;

            $components = [];
            if (! empty($parameters)) {
                $components[] = [
                    'type'       => 'body',
                    'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => $p], $parameters),
                ];
            }

            $response = Http::withToken($token)
                ->post("{$this->apiUrl}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $templateName,
                        'language'   => ['code' => $language],
                        'components' => $components,
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'success'    => true,
                    'simulated'  => false,
                    'message_id' => $response->json('messages.0.id'),
                ];
            }

            return [
                'success' => false,
                'error'   => $response->json('error.message', 'Error desconocido'),
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Marcar como leído ─────────────────────────────────────────────────────

    public function markAsRead(WhatsappAccount|null $account, string $messageId): array
    {
        if (! $account || ! $account->estaConfigurado()) {
            return ['success' => true, 'simulated' => true];
        }

        try {
            $token         = $account->access_token_decrypted;
            $phoneNumberId = $account->phone_number_id;

            $response = Http::withToken($token)
                ->post("{$this->apiUrl}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'status'            => 'read',
                    'message_id'        => $messageId,
                ]);

            return ['success' => $response->successful()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Formatear número ──────────────────────────────────────────────────────

    public function formatPhoneNumber(string $phone): string
    {
        // Quitar espacios, guiones, paréntesis
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        // Quitar + si empieza con él
        $phone = ltrim($phone, '+');
        return $phone;
    }

    // ── Validar cuenta ────────────────────────────────────────────────────────

    public function validateAccount(WhatsappAccount $account): array
    {
        if (! $account->estaConfigurado()) {
            return ['valid' => false, 'error' => 'No hay phone_number_id o access_token configurado.'];
        }

        try {
            $token         = $account->access_token_decrypted;
            $phoneNumberId = $account->phone_number_id;

            $response = Http::withToken($token)
                ->get("{$this->apiUrl}/{$phoneNumberId}");

            if ($response->successful()) {
                return ['valid' => true, 'data' => $response->json()];
            }

            return ['valid' => false, 'error' => $response->json('error.message', 'Error')];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }
}
