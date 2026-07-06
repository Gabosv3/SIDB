<?php

namespace App\Http\Controllers;

use App\Services\BaileysWhatsAppService;
use Illuminate\Http\Request;

class WhatsAppCenterController extends Controller
{
    protected BaileysWhatsAppService $baileys;

    public function __construct(BaileysWhatsAppService $baileys)
    {
        $this->baileys = $baileys;
    }

    /**
     * Mostrar dashboard principal de WhatsApp Center
     */
    public function index()
    {
        return view('whatsapp-center.dashboard');
    }

    /**
     * API: Obtener estado de conexión
     */
    public function status()
    {
        try {
            $status = $this->baileys->getStatus();
            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Enviar mensaje directo
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|string',
            'message' => 'required|string|max:4096',
        ]);

        try {
            $result = $this->baileys->sendMessage($validated['to'], $validated['message']);
            
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
     * API: Obtener información de cuenta
     */
    public function info()
    {
        try {
            $info = $this->baileys->getInfo();
            return response()->json($info);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Desconectar
     */
    public function disconnect()
    {
        try {
            $this->baileys->disconnect();
            return response()->json(['success' => true, 'message' => 'Desconectado']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Reconectar
     */
    public function reconnect()
    {
        try {
            $this->baileys->reconnect();
            return response()->json(['success' => true, 'message' => 'Reconectando...']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Obtener QR
     */
    public function qrcode()
    {
        try {
            $qr = $this->baileys->getQRCode();
            return response()->json($qr);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
