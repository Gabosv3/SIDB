<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GestionCobroResource\Pages;
use App\Models\GestionCobro;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class GestionCobroResource extends Resource
{
    protected static ?string $model = GestionCobro::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Gestiones de Cobro';
    }

    public static function getModelLabel(): string
    {
        return 'Gestión de Cobro';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Gestiones de Cobro';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información de la Gestión')
                ->columns(2)
                ->components([
                    Forms\Components\TextInput::make('numero_cuota')
                        ->label('N° Cuota')
                        ->disabled(),

                    Forms\Components\TextInput::make('total_cuotas')
                        ->label('Total de Cuotas')
                        ->disabled(),

                    Forms\Components\TextInput::make('monto_cuota')
                        ->label('Monto de la Cuota')
                        ->prefix('$')
                        ->disabled(),

                    Forms\Components\DatePicker::make('fecha_vencimiento')
                        ->label('Fecha de Vencimiento')
                        ->disabled(),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options([
                            'pendiente' => 'Pendiente',
                            'cobrado' => 'Cobrado',
                            'vencido' => 'Vencido',
                        ])
                        ->required(),

                    Forms\Components\Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venta.numero_venta')
                    ->label('N° Venta')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) => ($record->cliente?->nombre ?? '') . ' ' . ($record->cliente?->apellido ?? ''))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('numero_cuota')
                    ->label('Cuota')
                    ->formatStateUsing(fn ($state, $record) => "{$state}/{$record->total_cuotas}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_cuota')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_pagado')
                    ->label('Pagado')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'parcialmente_cobrado' => 'info',
                        'cobrado' => 'success',
                        'vencido' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'parcialmente_cobrado' => 'Parcialmente Pagado',
                        'cobrado' => 'Cobrado',
                        'vencido' => 'Vencido',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('mes_actual')
                    ->label('Del mes actual')
                    ->toggle()
                    ->default()
                    ->query(fn ($query) => $query
                        ->whereMonth('fecha_vencimiento', today()->month)
                        ->whereYear('fecha_vencimiento', today()->year)
                    ),

                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'cobrado' => 'Cobrado',
                        'vencido' => 'Vencido',
                    ]),

                Tables\Filters\Filter::make('vencimiento_hoy')
                    ->label('Vencen hoy')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('fecha_vencimiento', today())),

                Tables\Filters\Filter::make('vencimiento_proximo')
                    ->label('Vencen en los próximos 7 días')
                    ->toggle()
                    ->query(fn ($query) => $query
                        ->whereDate('fecha_vencimiento', '>=', today())
                        ->whereDate('fecha_vencimiento', '<=', today()->addDays(7))
                    ),

                Tables\Filters\Filter::make('vencidas')
                    ->label('Vencidas')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('fecha_vencimiento', '<', today())->where('estado', 'pendiente')),

                Tables\Filters\Filter::make('pendientes_cobrar')
                    ->label('Pendientes de cobrar')
                    ->toggle()
                    ->query(fn ($query) => $query->where('estado', 'pendiente')),
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
            ->defaultSort('fecha_vencimiento');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGestionCobros::route('/'),
            'edit' => Pages\EditGestionCobro::route('/{record}/edit'),
        ];
    }
}
