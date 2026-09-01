<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use App\Models\Categoria;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('conteoInventario')
                ->label('Conteo de inventario (PDF)')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('gray')
                ->modalHeading('Conteo de inventario')
                ->modalDescription('Elegí las categorías a incluir. Si no marcás ninguna, se incluyen todas.')
                ->modalSubmitActionLabel('Generar PDF')
                ->schema([
                    Forms\Components\CheckboxList::make('categorias')
                        ->label('Categorías')
                        ->options(fn () => Categoria::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                        ->columns(2)
                        ->bulkToggleable(),
                ])
                ->action(fn (array $data) => redirect(route('productos.conteo-inventario', [
                    'tenant' => \Filament\Facades\Filament::getTenant()?->id ?? 1,
                    'categoria_id' => $data['categorias'] ?? [],
                ]))),
            Actions\CreateAction::make(),
        ];
    }
}
