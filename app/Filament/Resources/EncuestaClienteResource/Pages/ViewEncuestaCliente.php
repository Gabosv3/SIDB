<?php

namespace App\Filament\Resources\EncuestaClienteResource\Pages;

use App\Filament\Resources\EncuestaClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEncuestaCliente extends ViewRecord
{
    protected static string $resource = EncuestaClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
