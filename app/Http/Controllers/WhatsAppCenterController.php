<?php

namespace App\Http\Controllers;

use App\Models\Cobrador;
use App\Models\PosDevice;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WhatsApp Monitor — panel de supervisión de solo lectura para WhatsApp
 * Coexistence: conversaciones de todos los cobradores/vendedores, estado de
 * los dispositivos (reutiliza pos_devices, ya existente para Monitor POS) y
 * un dashboard con el pulso general. No permite enviar mensajes.
 */
class WhatsAppCenterController extends Controller
{
    public function dashboard(Request $request, $tenant)
    {
        return view('whatsapp-monitor.dashboard', compact('tenant'));
    }

    public function conversaciones(Request $request, $tenant)
    {
        return view('whatsapp-monitor.conversaciones', compact('tenant'));
    }

    public function dashboardData(Request $request, $tenant): JsonResponse
    {
        $hoy = today();

        $dispositivos = PosDevice::with('cobrador:id,nombre,apellido')
            ->where('activo', true)
            ->orderByDesc('ultimo_ping')
            ->get()
            ->map(fn (PosDevice $d) => [
                'nombre' => $d->nombre,
                'cobrador' => $d->cobrador ? trim($d->cobrador->nombre.' '.$d->cobrador->apellido) : null,
                'estado' => $d->estado_calc,
                'bateria' => $d->bateria,
                'tiene_internet' => (bool) $d->tiene_internet,
                'ultimo_ping' => $d->ultimo_ping?->diffForHumans(),
            ]);

        $dispositivosOnline = $dispositivos->where('estado', 'activo')->count();

        $cobradoresTotal = Cobrador::where('activo', true)->where('excluir_reportes', false)->count();
        $cobradoresEnLinea = $dispositivos->where('estado', 'activo')->pluck('cobrador')->filter()->unique()->count();

        $conversacionesActivas = WhatsappConversation::where('estado', 'abierta')->count();

        $mensajesHoy = WhatsappMessage::whereDate('created_at', $hoy)->count();

        // Mensajes por hora de hoy (0-23), para el mini-gráfico de "Conversaciones por hora".
        $porHora = WhatsappMessage::whereDate('created_at', $hoy)
            ->selectRaw('HOUR(created_at) as hora, COUNT(*) as total')
            ->groupBy('hora')
            ->pluck('total', 'hora');
        $conversacionesPorHora = collect(range(0, 23))->map(fn ($h) => (int) ($porHora[$h] ?? 0))->values();

        $ultimosMensajes = WhatsappMessage::with('conversation.cliente:id,nombre,apellido', 'conversation.user:id,name')
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (WhatsappMessage $m) => [
                'cliente' => $m->conversation?->cliente?->nombre_completo ?? $m->conversation?->wa_id,
                'cobrador' => $m->conversation?->user?->name,
                'body' => $m->body,
                'direction' => $m->direction,
                'hora' => $m->created_at->format('H:i'),
            ]);

        return response()->json([
            'dispositivos_online' => $dispositivosOnline,
            'dispositivos_total' => $dispositivos->count(),
            'cobradores_en_linea' => $cobradoresEnLinea,
            'cobradores_total' => $cobradoresTotal,
            'conversaciones_activas' => $conversacionesActivas,
            'mensajes_hoy' => $mensajesHoy,
            'conversaciones_por_hora' => $conversacionesPorHora,
            'ultimos_mensajes' => $ultimosMensajes,
            'dispositivos' => $dispositivos->take(8)->values(),
        ]);
    }

    public function data(Request $request, $tenant): JsonResponse
    {
        $cobradorId = $request->integer('cobrador_id') ?: null;
        $buscar = trim((string) $request->get('buscar', ''));

        $conversaciones = WhatsappConversation::query()
            ->with(['cliente:id,nombre,apellido,codigo_anterior', 'user:id,name'])
            ->withCount('mensajes')
            ->when($cobradorId, fn ($q) => $q->where('user_id', $cobradorId))
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->whereHas('cliente', fn ($c) => $c
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido', 'like', "%{$buscar}%")
                    ->orWhere('codigo_anterior', 'like', "%{$buscar}%"));
            })
            ->orderByDesc('ultimo_mensaje_at')
            ->get()
            ->map(fn (WhatsappConversation $c) => [
                'id' => $c->id,
                'cobrador' => $c->user?->name,
                'cliente' => $c->cliente?->nombre_completo ?? $c->wa_id,
                'telefono' => $c->cliente?->telefono_whatsapp ?? $c->wa_id,
                'ultimo_mensaje' => $c->ultimo_mensaje,
                'ultimo_mensaje_at' => $c->ultimo_mensaje_at?->format('d/m/Y H:i'),
                'ultimo_mensaje_hora' => $c->ultimo_mensaje_at?->format('H:i'),
                'estado' => $c->estado,
                'mensajes_count' => $c->mensajes_count,
            ])
            ->values();

        $userIdsConConversacion = WhatsappConversation::whereNotNull('user_id')->distinct()->pluck('user_id');
        $cobradores = User::whereIn('id', $userIdsConConversacion)->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'conversaciones' => $conversaciones,
            'cobradores' => $cobradores,
            'total_conversaciones' => $conversaciones->count(),
            'total_cobradores' => $cobradores->count(),
            'total_numeros_conectados' => 0,
        ]);
    }

    public function mensajes(Request $request, $tenant, WhatsappConversation $conversacion): JsonResponse
    {
        $conversacion->load(['cliente:id,nombre,apellido,codigo_anterior,telefono_whatsapp', 'user:id,name', 'mensajes']);

        $primeraConversacion = WhatsappConversation::where('cliente_id', $conversacion->cliente_id)
            ->oldest('created_at')
            ->value('created_at');

        $totalConversacionesCliente = $conversacion->cliente_id
            ? WhatsappConversation::where('cliente_id', $conversacion->cliente_id)->count()
            : 1;

        $mensajesHoy = $conversacion->mensajes()->whereDate('created_at', today())->count();

        return response()->json([
            'cliente' => $conversacion->cliente?->nombre_completo ?? $conversacion->wa_id,
            'telefono' => $conversacion->cliente?->telefono_whatsapp ?? $conversacion->wa_id,
            'cobrador' => $conversacion->user?->name,
            'estado' => $conversacion->estado,
            'ultima_actividad' => $conversacion->ultimo_mensaje_at?->diffForHumans(),
            'mensajes' => $conversacion->mensajes->map(fn ($m) => [
                'direction' => $m->direction,
                'body' => $m->body,
                'hora' => $m->created_at->format('H:i'),
            ]),
            'resumen' => [
                'mensajes_hoy' => $mensajesHoy,
                'primera_conversacion' => $primeraConversacion?->format('d/m/Y'),
                'total_conversaciones' => $totalConversacionesCliente,
            ],
        ]);
    }
}
