<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\Venta;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVentas extends ListRecords
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Venta'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas'),
            'canceladas' => Tab::make('Historial de canceladas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'cancelada'))
                ->badge(fn () => Venta::where('estado', 'cancelada')->count()),
        ];
    }
}
