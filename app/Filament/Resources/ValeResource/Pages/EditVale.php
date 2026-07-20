<?php

namespace App\Filament\Resources\ValeResource\Pages;

use App\Filament\Resources\ValeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVale extends EditRecord
{
    protected static string $resource = ValeResource::class;

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
