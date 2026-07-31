<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use Filament\Actions;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewProducto extends ViewRecord
{
    protected static string $resource = ProductoResource::class;

    protected static ?string $title = 'Producto';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos básicos')
                ->columns(3)
                ->components([
                    TextEntry::make('nombre')
                        ->label('Nombre'),

                    TextEntry::make('codigo')
                        ->label('Código'),

                    TextEntry::make('categoria.nombre')
                        ->label('Categoría')
                        ->placeholder('—'),

                    TextEntry::make('precio_venta')
                        ->label('Precio de venta')
                        ->money('USD'),

                    TextEntry::make('stock')
                        ->label('Stock actual')
                        ->badge()
                        ->color(fn ($record) => $record->stockBajo() ? 'danger' : 'success'),

                    TextEntry::make('unidad_medida')
                        ->label('Unidad de medida'),

                    TextEntry::make('descripcion')
                        ->label('Descripción')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Imagen')
                ->components([
                    ImageEntry::make('imagen')
                        ->label('')
                        ->disk('public')
                        ->visibility('public')
                        ->height(200)
                        ->placeholder('Sin imagen'),
                ])
                ->visible(fn ($record) => filled($record->imagen)),
        ]);
    }
}
