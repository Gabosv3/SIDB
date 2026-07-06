<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\GestionCobro;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    public function __construct(protected WhatsAppCloudApiService $whatsapp) {}

    /**
     * Bandeja principal de conversaciones
     */
    public function index(Request $request, $tenant)
    {
        $filtro = $request->get('filtro', 'todos');
        $buscar = $request->get('q', '');

        $query = WhatsappConversation::with([
            'cliente:id,nombre,apellido,telefono_whatsapp,ruta_cobro_id',
            'ultimoMensaje',
        ])->orderByDesc('ultimo_mensaje_at');

        // Búsqueda por nombre de cliente
        if ($buscar) {
            $query->whereHas('cliente', function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apellido', 'like', "%{$buscar}%")
                  ->orWhere('telefono_whatsapp', 'like', "%{$buscar}%");
            });
        }

        if ($filtro === 'abiertas') {
            $query->where('estado', 'abierta');
        } elseif ($filtro === 'cerradas') {
            $query->where('estado', 'cerrada');
        } elseif ($filtro === 'sin_respuesta') {
            // Último mensaje fue saliente (out), nadie ha respondido
            $query->whereHas('ultimoMensaje', fn ($q) => $q->where('direction', 'out'));
        }

        $conversaciones = $query->paginate(30);

        // ── Estadísticas ──────────────────────────────────────────────────────
        $hace30Dias = \Carbon\Carbon::now()->subDays(30);

        $stats = [
            'conversaciones_activas' => WhatsappConversation::where('estado', 'abierta')->count(),
            'recordatorios_enviados' => WhatsappMessage::where('direction', 'out')
                ->where('created_at', '>=', $hace30Dias)
                ->count(),
            'cubiertas_confirmadas' => \App\Models\GestionCobro::where('estado', 'pagada')
                ->where('updated_at', '>=', $hace30Dias)
                ->count(),
            'respuestas_pendientes' => WhatsappConversation::whereHas(
                'ultimoMensaje',
                fn ($q) => $q->where('direction', 'out')
            )->count(),
        ];

        // Conversación activa si viene con ?c=ID
        $conversacionActiva = null;
        if ($request->has('c')) {
            $conversacionActiva = WhatsappConversation::with([
                'cliente',
                'mensajes',
            ])->find($request->c);
        }

        // Cuentas configuradas
        $cuentas = WhatsappAccount::where('estado', 'activo')->count();

        return view('whatsapp.index', compact(
            'conversaciones', 'conversacionActiva', 'filtro',
            'tenant', 'stats', 'buscar', 'cuentas'
        ));
    }

    /**
     * Cargar mensajes de una conversación (AJAX)
     */
    public function show($tenant, WhatsappConversation $conversation): JsonResponse
    {
        $conversation->load(['cliente:id,nombre,apellido,telefono_whatsapp', 'mensajes']);

        // Cuotas pendientes del cliente
        $cuotasPendientes = GestionCobro::where('cliente_id', $conversation->cliente_id)
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha_vencimiento')
            ->get(['id', 'numero_cuota', 'total_cuotas', 'monto_cuota', 'fecha_vencimiento', 'estado']);

        return response()->json([
            'conversation'    => $conversation,
            'cuotas_pendientes' => $cuotasPendientes,
        ]);
    }

    /**
     * DESHABILITADO: Enviar un mensaje al cliente (costo en Meta Cloud API)
     * 
     * El envío de mensajes fue deshabilitado el 2026-07-06 para cambiar a modo solo-lectura
     * Los costos de Meta Cloud API hacen que no sea viables enviar mensajes automáticos
     */
    public function send(Request $request, $tenant): JsonResponse
    {
        return response()->json([
            'error'   => 'Envío de mensajes deshabilitado',
            'motivo'  => 'Costos de Meta Cloud API',
            'estado'  => 'Solo lectura de mensajes disponible (webhook)',
        ], 403);
    }

    /**
     * DESHABILITADO: Enviar recordatorio de cuota rápido (costo en Meta Cloud API)
     * 
     * El envío de recordatorios fue deshabilitado el 2026-07-06 para cambiar a modo solo-lectura
     */
    public function sendReminder(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        return response()->json([
            'error'   => 'Envío de recordatorios deshabilitado',
            'motivo'  => 'Costos de Meta Cloud API',
            'estado'  => 'Solo lectura de mensajes disponible (webhook)',
        ], 403);
    }

    /**
     * DESHABILITADO: Crear o abrir conversación existente de un cliente (función auxiliar)
     * 
     * Sin envío de mensajes, esta funcionalidad no es necesaria
     */
    public function openClient(Request $request, $tenant, Cliente $cliente)
    {
        return response()->json([
            'error'   => 'Función deshabilitada',
            'motivo'  => 'Sin funcionalidad de envío de mensajes',
        ], 403);
    }

    /**
     * DESHABILITADO: Generar texto rápido con variables del cliente (costo en Meta Cloud API)
     * 
     * La funcionalidad de mensajes rápidos fue deshabilitada el 2026-07-06
     */
    public function quickMessage(Request $request, $tenant): JsonResponse
    {
        return response()->json([
            'error'   => 'Mensajes rápidos deshabilitados',
            'motivo'  => 'Costos de Meta Cloud API',
            'estado'  => 'Solo lectura de mensajes disponible (webhook)',
        ], 403);
    }

    // ── Helper interno ─────────────────────────────────────────────────────────

    private function crearOAbrirConversacion(Cliente $cliente): WhatsappConversation
    {
        return WhatsappConversation::firstOrCreate(
            ['cliente_id' => $cliente->id],
            [
                'user_id'        => auth()->id(),
                'wa_id'          => $cliente->telefono_whatsapp,
                'estado'         => 'abierta',
            ]
        );
    }
}
