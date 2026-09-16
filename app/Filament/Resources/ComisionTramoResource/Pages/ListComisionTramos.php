<?php

namespace App\Filament\Resources\ComisionTramoResource\Pages;

use App\Filament\Resources\ComisionTramoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComisionTramos extends ListRecords
{
    protected static string $resource = ComisionTramoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Agregar tramo'),
        ];
    }
}
