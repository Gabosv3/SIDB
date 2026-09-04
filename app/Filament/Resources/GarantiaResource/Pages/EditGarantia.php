<?php

namespace App\Filament\Resources\GarantiaResource\Pages;

use App\Filament\Resources\GarantiaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGarantia extends EditRecord
{
    protected static string $resource = GarantiaResource::class;

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
