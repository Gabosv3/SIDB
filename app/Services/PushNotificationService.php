<?php

namespace App\Services;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Envía push notifications vía la API HTTP de Expo (https://exp.host/--/api/v2/push/send).
// No requiere Firebase/APNs configurado a mano: Expo actúa de intermediario para
// cualquier app construida con `expo-notifications`, incluida esta.
class PushNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Envía la misma notificación a todos los tokens registrados de un usuario.
     * Los tokens que Expo reporte como inválidos (dispositivo desinstaló la app,
     * etc.) se eliminan para no seguir intentando enviarles.
     */
    public function enviarAUsuario(User $user, string $titulo, string $cuerpo, array $data = []): void
    {
        $tokens = $user->pushTokens()->pluck('token');
        if ($tokens->isEmpty()) {
            return;
        }

        $this->enviar($tokens->all(), $titulo, $cuerpo, $data);
    }

    /**
     * Envía a una lista de tokens Expo directamente (útil cuando ya se agruparon
     * varios usuarios). Expo acepta hasta 100 mensajes por request — se parte en
     * lotes si hace falta.
     */
    public function enviar(array $tokens, string $titulo, string $cuerpo, array $data = []): void
    {
        $tokensValidos = array_values(array_filter($tokens, fn ($t) => str_starts_with($t, 'ExponentPushToken')));
        if (empty($tokensValidos)) {
            return;
        }

        foreach (array_chunk($tokensValidos, 100) as $lote) {
            $mensajes = array_map(fn ($token) => [
                'to'    => $token,
                'title' => $titulo,
                'body'  => $cuerpo,
                'data'  => $data,
                'sound' => 'default',
            ], $lote);

            try {
                $response = Http::timeout(10)->post(self::EXPO_PUSH_URL, $mensajes);

                if ($response->successful()) {
                    $this->limpiarTokensInvalidos($lote, $response->json('data', []));
                } else {
                    Log::warning('Expo push respondió con error', ['status' => $response->status(), 'body' => $response->body()]);
                }
            } catch (\Throwable $e) {
                // Un fallo de red al notificar no debe tumbar el comando que lo dispara
                // (ej. el cron de cuotas vencidas) — solo se registra.
                Log::warning('No se pudo enviar push notification: ' . $e->getMessage());
            }
        }
    }

    private function limpiarTokensInvalidos(array $tokensEnviados, array $resultados): void
    {
        foreach ($resultados as $i => $resultado) {
            if (($resultado['status'] ?? null) === 'error' && ($resultado['details']['error'] ?? null) === 'DeviceNotRegistered') {
                $token = $tokensEnviados[$i] ?? null;
                if ($token) {
                    PushToken::where('token', $token)->delete();
                }
            }
        }
    }
}
