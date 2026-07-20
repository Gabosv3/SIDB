<?php

namespace App\Filament\Resources\VehiculoResource\Pages;

use App\Filament\Resources\VehiculoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVehiculo extends CreateRecord
{
    protected static string $resource = VehiculoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
