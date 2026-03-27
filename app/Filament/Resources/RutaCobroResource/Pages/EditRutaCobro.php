<?php

namespace App\Filament\Resources\RutaCobroResource\Pages;

use App\Filament\Resources\RutaCobroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRutaCobro extends EditRecord
{
    protected static string $resource = RutaCobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('ver-mapa')
                ->label('Ver Mapa')
                ->icon('heroicon-m-map')
                ->url(fn($record) => route('ruta.mapa', $record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
