<?php

namespace App\Filament\Resources\VendedorResource\Pages;

use App\Filament\Resources\VendedorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendedores extends ListRecords
{
    protected static string $resource = VendedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuevo Vendedor'),
        ];
    }
}
