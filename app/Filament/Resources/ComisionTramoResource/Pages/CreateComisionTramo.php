<?php

namespace App\Filament\Resources\ComisionTramoResource\Pages;

use App\Filament\Resources\ComisionTramoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateComisionTramo extends CreateRecord
{
    protected static string $resource = ComisionTramoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
