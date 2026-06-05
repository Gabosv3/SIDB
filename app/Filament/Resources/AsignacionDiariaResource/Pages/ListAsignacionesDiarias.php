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
            Actions\Action::make('reporte')
                ->label('📊 Descargar Reporte PDF')
                ->button()
                ->color('info')
                ->icon('heroicon-m-document-text')
                ->url(fn () => route('reporte.diario', ['tenant' => \Filament\Facades\Filament::getTenant()->id]))
                ->openUrlInNewTab(),

            Actions\Action::make('create')
                ->label('Nueva asignación diaria')
                ->button()
                ->url(fn () => route('asignacion-diaria.crear', ['tenant' => \Filament\Facades\Filament::getTenant()->id])),
        ];
    }
}
