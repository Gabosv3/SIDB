<?php

namespace App\Filament\Resources\VentaResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    protected static ?string $title = 'Registro de Pagos';

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

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable()
                    ->weight('semibold'),

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

                Tables\Columns\TextColumn::make('observaciones')
                    ->label('Notas')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('fecha_pago', 'desc');
    }
}
