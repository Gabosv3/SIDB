<?php

namespace App\Filament\Resources\RutaCobroResource\Pages;

use App\Filament\Resources\RutaCobroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRutasCobro extends ListRecords
{
    protected static string $resource = RutaCobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
