<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PagosVentaRelationManager extends RelationManager
{
    protected static string $relationship = 'pagosVenta';

    protected static ?string $title = 'Historial de Pagos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('monto')
            ->columns([
                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('venta.numero_venta')
                    ->label('Venta')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('USD')
                    ->weight('semibold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('metodo_pago')
                    ->label('Método')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'efectivo'      => 'success',
                        'transferencia' => 'info',
                        'cheque'        => 'warning',
                        'deposito'      => 'primary',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'efectivo'      => 'Efectivo',
                        'transferencia' => 'Transferencia',
                        'cheque'        => 'Cheque',
                        'deposito'      => 'Depósito',
                        default         => $state,
                    }),

                Tables\Columns\TextColumn::make('referencia')
                    ->label('Referencia')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Registró')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('metodo_pago')
                    ->label('Método')
                    ->options([
                        'efectivo'      => 'Efectivo',
                        'transferencia' => 'Transferencia',
                        'cheque'        => 'Cheque',
                        'deposito'      => 'Depósito',
                    ]),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('fecha_pago', 'desc');
    }
}