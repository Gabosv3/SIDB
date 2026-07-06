<?php

namespace App\Filament\Pages;

use App\Services\BaileysWhatsAppService;
use Filament\Pages\Page;

class BaileysCenter extends Page
{
    protected static ?string $navigationLabel = 'Baileys WhatsApp';
    protected static ?string $title = 'Centro WhatsApp - Baileys';
    protected static ?int $navigationSort = 1;
    protected string $view = 'whatsapp.baileys-center';

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
}
