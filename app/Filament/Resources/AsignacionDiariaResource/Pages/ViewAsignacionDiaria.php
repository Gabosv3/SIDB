<?php

namespace App\Filament\Resources\AsignacionDiariaResource\Pages;

use App\Filament\Resources\AsignacionDiariaResource;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ViewAsignacionDiaria extends ViewRecord
{
    protected static string $resource = AsignacionDiariaResource::class;

    protected static ?string $title = 'Detalle de Asignacion Diaria';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->estaActiva()),

            Actions\Action::make('liquidar')
                ->label('Liquidar jornada')
                ->icon('heroicon-m-calculator')
                ->color('warning')
                ->visible(fn () => $this->record->estaActiva())
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->liquidar();
                    $this->record->refresh();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Asignacion')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Jornada')
                        ->icon('heroicon-m-calendar-days')
                        ->components([
                            Section::make('Resumen')
                                ->columns(3)
                                ->components([
                                    TextEntry::make('fecha')
                                        ->label('Fecha')
                                        ->date('d/m/Y'),

                                    TextEntry::make('estado')
                                        ->label('Estado')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'activa' => 'success',
                                            'liquidada' => 'gray',
                                            default => 'gray',
                                        }),

                                    TextEntry::make('liquidada_at')
                                        ->label('Liquidada')
                                        ->dateTime('d/m/Y H:i')
                                        ->placeholder('-'),

                                    TextEntry::make('vendedor.nombre_completo')
                                        ->label('Vendedor'),

                                    TextEntry::make('sucursal.nombre')
                                        ->label('Sucursal'),

                                    TextEntry::make('detalles_count')
                                        ->label('Productos')
                                        ->state(fn ($record) => $record->detalles()->count())
                                        ->badge()
                                        ->color('info'),
                                ]),

                            Section::make('Totales')
                                ->columns(2)
                                ->components([
                                    TextEntry::make('total_vendido')
                                        ->label('Total vendido')
                                        ->money('USD')
                                        ->placeholder('-')
                                        ->weight('bold'),

                                    TextEntry::make('total_devuelto_valor')
                                        ->label('Valor devuelto')
                                        ->money('USD')
                                        ->placeholder('-'),
                                ]),

                            Section::make('Observaciones')
                                ->components([
                                    TextEntry::make('observaciones')
                                        ->label('')
                                        ->placeholder('-')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Productos')
                        ->icon('heroicon-m-shopping-bag')
                        ->components([
                            Section::make('Detalle de productos')
                                ->columnSpanFull()
                                ->components([
                                    RepeatableEntry::make('detalles')
                                        ->label('')
                                        ->columns(6)
                                        ->schema([
                                            TextEntry::make('producto.nombre')
                                                ->label('Producto')
                                                ->columnSpan(2),

                                            TextEntry::make('cantidad_asignada')
                                                ->label('Asignada')
                                                ->badge()
                                                ->color('info'),

                                            TextEntry::make('cantidad_vendida')
                                                ->label('Vendida')
                                                ->badge()
                                                ->color('success'),

                                            TextEntry::make('cantidad_disponible')
                                                ->label('Disponible')
                                                ->badge()
                                                ->color('primary')
                                                ->suffix('un.'),

                                            TextEntry::make('cantidad_devuelta')
                                                ->label('Devuelta')
                                                ->badge()
                                                ->color('warning'),

                                            TextEntry::make('precio_venta')
                                                ->label('Precio')
                                                ->money('USD'),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }
}
