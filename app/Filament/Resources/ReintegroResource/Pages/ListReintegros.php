<?php

namespace App\Filament\Resources\ReintegroResource\Pages;

use App\Filament\Resources\ReintegroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReintegros extends ListRecords
{
    protected static string $resource = ReintegroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Asignar reintegro'),
        ];
    }
}
