<?php

namespace App\Filament\Resources\MovimientoStockResource\Pages;

use App\Filament\Resources\MovimientoStockResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMovimientoStock extends CreateRecord
{
    protected static string $resource = MovimientoStockResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
