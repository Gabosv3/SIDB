<?php

namespace App\Filament\Resources\ComisionTramoResource\Pages;

use App\Filament\Resources\ComisionTramoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComisionTramo extends EditRecord
{
    protected static string $resource = ComisionTramoResource::class;

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
