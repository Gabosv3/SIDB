<?php

namespace App\Http\Controllers;

use App\Models\BaileysSession;
use App\Models\WhatsappMessage;
use App\Services\BaileysWhatsAppService;
use Illuminate\Http\Request;

class WhatsAppCenterController extends Controller
{
    protected BaileysWhatsAppService $baileys;

    public function __construct(BaileysWhatsAppService $baileys)
    {
        $this->baileys = $baileys;
    }

    private function sessionKey(): string
    {
        return BaileysSession::sessionKeyPara(auth()->id());
    }

    /**
     * Mostrar dashboard "Mi WhatsApp" — cada usuario ve/gestiona únicamente su propia sesión
     */
    public function index()
    {
        BaileysSession::firstOrCreate(
            ['user_id' => auth()->id()],
            ['session_key' => $this->sessionKey()]
        );

        return view('whatsapp-center.dashboard');
    }

    /**
     * API: iniciar (o reanudar) la sesión propia
     */
    public function connect()
    {
        try {
            $resultado = $this->baileys->connect($this->sessionKey());
            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Obtener estado de conexión de la sesión propia
     */
    public function status()
    {
        try {
            $status = $this->baileys->getStatus($this->sessionKey());
            $info = $status['connected'] ?? false ? $this->baileys->getInfo($this->sessionKey()) : [];

            BaileysSession::sincronizarDesdeEstado(auth()->id(), $status, $info);

            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Enviar mensaje directo desde la sesión propia
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|string',
            'message' => 'required|string|max:4096',
        ]);

        try {
            $result = $this->baileys->sendMessage($this->sessionKey(), $validated['to'], $validated['message']);

            if (empty($result['success'])) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'No se pudo enviar el mensaje',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado correctamente',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * API: Obtener información de cuenta de la sesión propia
     */
    public function info()
    {
        try {
            $info = $this->baileys->getInfo($this->sessionKey());
            return response()->json($info);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Desconectar la sesión propia
     */
    public function disconnect()
    {
        try {
            $this->baileys->disconnect($this->sessionKey());
            BaileysSession::where('user_id', auth()->id())->update(['estado' => 'desconectado']);
            return response()->json(['success' => true, 'message' => 'Desconectado']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Reconectar (nuevo QR) la sesión propia
     */
    public function reconnect()
    {
        try {
            $this->baileys->reconnect($this->sessionKey());
            BaileysSession::where('user_id', auth()->id())->update(['estado' => 'esperando_qr']);
            return response()->json(['success' => true, 'message' => 'Reconectando...']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Obtener QR de la sesión propia
     */
    public function qrcode()
    {
        try {
            $qr = $this->baileys->getQRCode($this->sessionKey());
            return response()->json(['qr' => $qr]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: mensajes enviados/recibidos por el usuario autenticado
     */
    public function stats()
    {
        $sent = WhatsappMessage::where('direction', 'out')
            ->whereHas('conversation', fn ($q) => $q->where('user_id', auth()->id()))
            ->count();

        $received = WhatsappMessage::where('direction', 'in')
            ->whereHas('conversation', fn ($q) => $q->where('user_id', auth()->id()))
            ->count();

        return response()->json(['sent' => $sent, 'received' => $received]);
    }
}
