<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComisionTramoResource\Pages;
use App\Models\ComisionTramo;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ComisionTramoResource extends Resource
{
    protected static ?string $model = ComisionTramo::class;

    protected static bool $isScopedToTenant = false;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar-square';
    }

    public static function getNavigationLabel(): string
    {
        return 'Tramos de Comisión';
    }

    public static function getModelLabel(): string
    {
        return 'Tramo de comisión';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tramos de comisión';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Ventas';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('desde')
                ->label('Desde ($)')
                ->numeric()
                ->prefix('$')
                ->minValue(0)
                ->step(0.01)
                ->required()
                ->helperText('Monto vendido en la semana a partir del cual aplica este porcentaje.'),

            Forms\Components\TextInput::make('hasta')
                ->label('Hasta ($)')
                ->numeric()
                ->prefix('$')
                ->minValue(0)
                ->step(0.01)
                ->nullable()
                ->helperText('Dejar vacío para "sin límite superior" (el último tramo).'),

            Forms\Components\TextInput::make('porcentaje')
                ->label('Porcentaje de comisión')
                ->numeric()
                ->suffix('%')
                ->minValue(0)
                ->maxValue(100)
                ->step(0.01)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('desde')
                    ->label('Desde')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hasta')
                    ->label('Hasta')
                    ->money('USD')
                    ->placeholder('Sin límite')
                    ->sortable(),

                Tables\Columns\TextColumn::make('porcentaje')
                    ->label('Porcentaje')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2).'%')
                    ->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('desde');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListComisionTramos::route('/'),
            'create' => Pages\CreateComisionTramo::route('/create'),
            'edit'   => Pages\EditComisionTramo::route('/{record}/edit'),
        ];
    }
}
