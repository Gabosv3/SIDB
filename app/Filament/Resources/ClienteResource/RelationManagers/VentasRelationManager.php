<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use App\Models\PagoVenta;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VentasRelationManager extends RelationManager
{
    protected static string $relationship = 'ventas';

    protected static ?string $title = 'Historial de compras';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_venta')
            ->columns([
                Tables\Columns\TextColumn::make('numero_venta')
                    ->label('N° Venta')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('fecha_venta')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_pago')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'contado' => 'success',
                        'credito' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'contado' => 'Contado',
                        'credito' => 'Crédito',
                        default   => $state,
                    }),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_pagado')
                    ->label('Pagado')
                    ->money('USD')
                    ->color('success'),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo')
                    ->money('USD')
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'  => 'warning',
                        'completada' => 'success',
                        'cancelada'  => 'danger',
                        'devuelta'   => 'info',
                        default      => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'completada' => 'Completada',
                        'cancelada'  => 'Cancelada',
                        'devuelta'   => 'Devuelta',
                    ]),

                Tables\Filters\Filter::make('con_saldo')
                    ->label('Con saldo pendiente')
                    ->query(fn ($query) => $query->where('saldo_pendiente', '>', 0))
                    ->toggle(),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->label('Ver venta')
                    ->url(fn ($record) => \App\Filament\Resources\VentaResource::getUrl('view', ['record' => $record])),

                Actions\Action::make('ver_pagos')
                    ->label('Pagos')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->visible(fn ($record): bool => $record->tipo_pago === 'credito')
                    ->modalHeading(fn ($record): string => 'Pagos — ' . $record->numero_venta)
                    ->modalIcon('heroicon-o-banknotes')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn ($record) => view(
                        'filament.modals.pagos-venta',
                        ['venta' => $record->load('pagos.user')]
                    )),
            ])
            ->bulkActions([])
            ->defaultSort('fecha_venta', 'desc');
    }
}
