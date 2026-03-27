<?php

namespace App\Filament\Resources\CobradorResource\Pages;

use App\Filament\Resources\CobradorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCobrador extends EditRecord
{
    protected static string $resource = CobradorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
