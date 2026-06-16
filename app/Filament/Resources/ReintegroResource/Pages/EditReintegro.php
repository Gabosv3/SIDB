<?php

namespace App\Filament\Resources\ReintegroResource\Pages;

use App\Filament\Resources\ReintegroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReintegro extends EditRecord
{
    protected static string $resource = ReintegroResource::class;

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
