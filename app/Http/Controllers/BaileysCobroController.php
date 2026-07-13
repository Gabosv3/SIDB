<?php

namespace App\Http\Controllers;

use App\Models\BaileysSession;
use App\Models\Cliente;
use App\Models\GestionCobro;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\BaileysWhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaileysCobroController extends Controller
{
    public function __construct(protected BaileysWhatsAppService $baileys) {}

    /**
     * Enviar recordatorio de pago a cliente
     */
    public function enviarRecordatorio($clienteId): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            if (!$cliente->telefono_whatsapp) {
                return response()->json([
                    'error' => 'El cliente no tiene número WhatsApp registrado',
                ], 422);
            }

            // Obtener próxima cuota pendiente
            $cuota = $cliente->gestionesCobro()
                ->whereIn('estado', ['pendiente', 'vencida'])
                ->orderBy('fecha_vencimiento')
                ->first();

            if (!$cuota) {
                return response()->json([
                    'error' => 'Sin cuotas pendientes',
                ], 404);
            }

            // Construir mensaje
            $mensaje = "Hola {$cliente->nombre}, le recordamos que tiene una cuota pendiente por \${$cuota->monto_cuota} con vencimiento el {$cuota->fecha_vencimiento->format('d/m/Y')}. Por favor comuníquese con nosotros. Distribuidora Briancesco Menjivar.";

            // Enviar por Baileys, desde la sesión propia del empleado autenticado
            $resultado = $this->baileys->sendMessage(
                BaileysSession::sessionKeyPara(auth()->id()),
                $cliente->telefono_whatsapp,
                $mensaje
            );

            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $resultado['error'] ?? 'Error desconocido',
                ], 500);
            }

            $this->registrarMensajeSaliente($cliente, $mensaje, $resultado['messageId'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Recordatorio enviado',
                'cliente' => $cliente->nombre,
                'cuota' => $cuota->numero_cuota,
                'monto' => $cuota->monto_cuota,
                'messageId' => $resultado['messageId'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar mensaje personalizado al cliente
     */
    public function enviarMensajePersonalizado(Request $request, $clienteId): JsonResponse
    {
        try {
            $request->validate([
                'mensaje' => 'required|string|max:4096',
            ]);

            $cliente = Cliente::findOrFail($clienteId);

            if (!$cliente->telefono_whatsapp) {
                return response()->json([
                    'error' => 'El cliente no tiene número WhatsApp registrado',
                ], 422);
            }

            // Enviar mensaje, desde la sesión propia del empleado autenticado
            $resultado = $this->baileys->sendMessage(
                BaileysSession::sessionKeyPara(auth()->id()),
                $cliente->telefono_whatsapp,
                $request->mensaje
            );

            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $resultado['error'] ?? 'Error desconocido',
                ], 500);
            }

            $this->registrarMensajeSaliente($cliente, $request->mensaje, $resultado['messageId'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado',
                'cliente' => $cliente->nombre,
                'messageId' => $resultado['messageId'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Estado de la sesión de Baileys del empleado autenticado
     */
    public function status(): JsonResponse
    {
        $sessionKey = BaileysSession::sessionKeyPara(auth()->id());
        $status = $this->baileys->getStatus($sessionKey);
        $info = $this->baileys->getInfo($sessionKey);

        return response()->json([
            'connected' => $status['connected'] ?? false,
            'pending_qr' => $status['qrCodePending'] ?? false,
            'account' => $info,
        ]);
    }

    /**
     * Deja registro en whatsapp_conversations/whatsapp_messages del mensaje saliente,
     * para que el historial "quién le escribió qué a quién" quede completo.
     */
    private function registrarMensajeSaliente(Cliente $cliente, string $mensaje, ?string $waMessageId): void
    {
        $conversacion = WhatsappConversation::firstOrCreate(
            ['cliente_id' => $cliente->id, 'user_id' => auth()->id()],
            ['wa_id' => $cliente->telefono_whatsapp, 'estado' => 'abierta']
        );

        $conversacion->update([
            'ultimo_mensaje' => $mensaje,
            'ultimo_mensaje_at' => now(),
            'estado' => 'abierta',
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conversacion->id,
            'wa_message_id' => $waMessageId,
            'direction' => 'out',
            'type' => 'text',
            'body' => $mensaje,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
