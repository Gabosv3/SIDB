<?php

namespace App\Filament\Resources\GestionCobroResource\Pages;

use App\Filament\Resources\GestionCobroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGestionCobro extends EditRecord
{
    protected static string $resource = GestionCobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
