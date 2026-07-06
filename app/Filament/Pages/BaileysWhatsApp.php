<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class BaileysWhatsApp extends Page
{
    protected static ?string $navigationLabel = 'Baileys WhatsApp';
    protected static ?string $title = 'Centro WhatsApp - Baileys';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.baileys-whatsapp';
    
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getNavigationGroup(): string|\BackedEnum|null
    {
        return 'Cobros';
    }
}
