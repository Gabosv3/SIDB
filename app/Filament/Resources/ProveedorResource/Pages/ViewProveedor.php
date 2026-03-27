<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewProveedor extends ViewRecord
{
    protected static string $resource = ProveedorResource::class;

    protected static ?string $title = 'Ver Proveedor';

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información General')
                ->columns(2)
                ->components([
                    TextEntry::make('nombre')->label('Nombre'),
                    TextEntry::make('codigo')
                        ->label('Código')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('contacto_principal')
                        ->label('Contacto Principal'),
                    TextEntry::make('email')->label('Email'),
                    TextEntry::make('telefono')->label('Teléfono'),
                    IconEntry::make('activo')
                        ->label('Estado')
                        ->boolean(),
                ]),

            Section::make('Dirección')
                ->columns(2)
                ->components([
                    TextEntry::make('direccion')->label('Dirección')->columnSpanFull(),
                    TextEntry::make('ciudad')->label('Ciudad'),
                    TextEntry::make('departamento')->label('Departamento'),
                    TextEntry::make('pais')->label('País'),
                    TextEntry::make('codigo_postal')->label('Código Postal'),
                ]),

            Section::make('Términos Comerciales')
                ->columns(3)
                ->components([
                    TextEntry::make('condiciones_pago')
                        ->label('Condición de Pago')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'contado' => 'success',
                            'credito' => 'warning',
                            'mixto'   => 'info',
                            default   => 'gray',
                        }),
                    TextEntry::make('dias_credito')->label('Días de Crédito'),
                    TextEntry::make('descuento_comercial')->label('Descuento (%)'),
                ]),
        ]);
    }
}
