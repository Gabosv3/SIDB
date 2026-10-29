<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Botón exportar CSV
            Actions\Action::make('exportar_csv')
                ->label('Descargar CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(route('clientes.exportar.csv'))
                ->openUrlInNewTab(),

            Actions\CreateAction::make(),
        ];
    }
}
