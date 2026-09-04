<?php

namespace App\Filament\Resources\GarantiaResource\Pages;

use App\Filament\Resources\GarantiaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGarantias extends ListRecords
{
    protected static string $resource = GarantiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Registrar garantía'),
        ];
    }
}
