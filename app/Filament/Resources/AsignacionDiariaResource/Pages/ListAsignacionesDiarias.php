<?php

namespace App\Filament\Resources\AsignacionDiariaResource\Pages;

use App\Filament\Resources\AsignacionDiariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAsignacionesDiarias extends ListRecords
{
    protected static string $resource = AsignacionDiariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('Nueva asignación diaria')
                ->button()
                ->url(fn () => route('asignacion-diaria.crear')),
        ];
    }
}
