<?php

namespace App\Filament\Resources\VendedorResource\Pages;

use App\Filament\Resources\VendedorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendedor extends CreateRecord
{
    protected static string $resource = VendedorResource::class;

    protected static ?string $title = 'Nuevo Vendedor';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
