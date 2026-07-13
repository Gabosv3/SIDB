<?php

namespace App\Filament\Pages;

use App\Models\BaileysSession;
use App\Models\WhatsappConversation;
use App\Services\BaileysWhatsAppService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class BaileysCenter extends Page
{
    protected static ?string $navigationLabel = 'Baileys WhatsApp';
    protected static ?string $title = 'Centro WhatsApp - Baileys';
    protected static ?int $navigationSort = 1;
    protected string $view = 'whatsapp.baileys-center';

    public ?int $verMensajesDeUserId = null;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getNavigationGroup(): string|\BackedEnum|null
    {
        return 'Cobros';
    }

    public function mount(): void
    {
        // Verificar que Baileys esté disponible
        $baileys = app(BaileysWhatsAppService::class);
        if (!$baileys->isAvailable()) {
            // Log pero no bloquear el acceso
            \Log::warning('⚠️ Servidor Baileys no disponible en ' . date('Y-m-d H:i:s'));
        }
    }

    /**
     * Sesiones de WhatsApp de todos los vendedores/cobradores, para el resumen del admin.
     */
    public function getSesiones(): Collection
    {
        return BaileysSession::with('user')
            ->whereHas('user')
            ->get()
            ->sortByDesc('ultima_actividad_at')
            ->values();
    }

    /**
     * Conversaciones del empleado seleccionado (con su último mensaje), para el panel
     * de "Ver mensajes" — sin reconstruir una UI de chat completa.
     */
    public function getConversacionesDeUsuario(): Collection
    {
        if (! $this->verMensajesDeUserId) {
            return collect();
        }

        return WhatsappConversation::with(['cliente', 'mensajes' => fn ($q) => $q->orderByDesc('created_at')])
            ->where('user_id', $this->verMensajesDeUserId)
            ->orderByDesc('ultimo_mensaje_at')
            ->get();
    }
}
