<?php

namespace App\Filament\Resources\PreventaResource\Pages;

use App\Filament\Resources\PreventaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPreventa extends EditRecord
{
    protected static string $resource = PreventaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
