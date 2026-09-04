<?php

namespace App\Filament\Resources\EncuestaClienteResource\Pages;

use App\Filament\Resources\EncuestaClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEncuestaCliente extends EditRecord
{
    protected static string $resource = EncuestaClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
