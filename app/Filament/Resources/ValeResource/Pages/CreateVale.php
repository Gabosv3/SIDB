<?php

namespace App\Filament\Resources\ValeResource\Pages;

use App\Filament\Resources\ValeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVale extends CreateRecord
{
    protected static string $resource = ValeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
