<?php

namespace App\Filament\Resources\GestionCobroResource\Pages;

use App\Filament\Resources\GestionCobroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGestionCobros extends ListRecords
{
    protected static string $resource = GestionCobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction no es necesario aquí porque las gestiones se crean automáticamente
        ];
    }
}
