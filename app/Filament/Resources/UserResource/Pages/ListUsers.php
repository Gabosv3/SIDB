<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('fichaDatosBlanco')
                ->label('Ficha de datos (en blanco)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('empleados.ficha-datos-blanco', \Filament\Facades\Filament::getTenant()?->id ?? 1))
                ->openUrlInNewTab(),
            Actions\CreateAction::make(),
        ];
    }
}
