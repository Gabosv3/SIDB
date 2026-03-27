<?php

namespace App\Filament\Resources\CompraResource\Pages;

use App\Filament\Resources\CompraResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewCompra extends ViewRecord
{
    protected static string $resource = CompraResource::class;

    protected static ?string $title = 'Ver Compra';

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información General')
                ->columns(3)
                ->components([
                    TextEntry::make('numero_compra')
                        ->label('Número')
                        ->badge()
                        ->color('primary'),
                    TextEntry::make('fecha_compra')
                        ->label('Fecha')
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('estado')
                        ->label('Estado')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pendiente'  => 'warning',
                            'recibida'   => 'info',
                            'completada' => 'success',
                            'cancelada'  => 'danger',
                            default      => 'gray',
                        }),
                ]),

            Section::make('Proveedor')
                ->columns(2)
                ->components([
                    TextEntry::make('proveedor.nombre')->label('Nombre'),
                    TextEntry::make('proveedor.email')->label('Email'),
                    TextEntry::make('proveedor.telefono')->label('Teléfono'),
                    TextEntry::make('proveedor.ciudad')->label('Ciudad'),
                ]),

            Section::make('Artículos')
                ->columnSpanFull()
                ->components([
                    RepeatableEntry::make('detalles')
                        ->label('Detalle de Compra')
                        ->columns(4)
                        ->schema([
                            TextEntry::make('producto.nombre')->label('Producto'),
                            TextEntry::make('cantidad')->label('Cantidad'),
                            TextEntry::make('precio_unitario')
                                ->label('Precio')
                                ->money('MXN'),
                            TextEntry::make('subtotal')
                                ->label('Subtotal')
                                ->money('MXN'),
                        ]),
                ]),

            Section::make('Totales')
                ->columns(3)
                ->components([
                    TextEntry::make('subtotal')
                        ->label('Subtotal')
                        ->money('MXN'),
                    TextEntry::make('descuento_monto')
                        ->label('Descuento')
                        ->money('MXN'),
                    TextEntry::make('impuesto_monto')
                        ->label('Impuesto')
                        ->money('MXN'),
                    TextEntry::make('total')
                        ->label('Total')
                        ->money('MXN')
                        ->weight('bold'),
                    TextEntry::make('saldo_pendiente')
                        ->label('Saldo Pendiente')
                        ->money('MXN')
                        ->color(fn (string $state): string =>
                            (float)$state > 0 ? 'danger' : 'success'
                        ),
                ]),
        ]);
    }
}
