<?php

namespace App\Filament\Resources\RutaCobroResource\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ClientesRelationManager extends RelationManager
{
    protected static string $relationship = 'clientes';

    protected static ?string $recordTitleAttribute = 'nombre_completo';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre_completo')
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Cliente')
                    ->searchable(['nombre', 'apellido'])
                    ->sortable(['nombre'])
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('telefono_normal')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->icon('heroicon-m-envelope')
                    ->searchable(),

                Tables\Columns\TextColumn::make('municipio')
                    ->label('Municipio')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('nombre');
    }
}
