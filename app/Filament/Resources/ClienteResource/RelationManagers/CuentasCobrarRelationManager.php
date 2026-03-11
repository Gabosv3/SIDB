<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CuentasCobrarRelationManager extends RelationManager
{
    protected static string $relationship = 'cuentasCobrar';

    protected static ?string $title = 'Cuentas a cobrar';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('concepto')
                ->label('Concepto')
                ->placeholder('Ej: Venta a crédito #001')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('monto')
                ->label('Monto')
                ->numeric()
                ->prefix('$')
                ->required()
                ->minValue(0.01),

            Forms\Components\DatePicker::make('fecha_vencimiento')
                ->label('Fecha de vencimiento')
                ->native(false)
                ->displayFormat('d/m/Y'),

            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options([
                    'pendiente' => 'Pendiente',
                    'pagado'    => 'Pagado',
                    'vencido'   => 'Vencido',
                ])
                ->default('pendiente')
                ->required(),

            Forms\Components\Textarea::make('observaciones')
                ->label('Observaciones')
                ->rows(3)
                ->maxLength(1000)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('concepto')
            ->columns([
                Tables\Columns\TextColumn::make('concepto')
                    ->label('Concepto')
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Sin fecha')
                    ->color(fn ($record) => $record?->estado === 'vencido' ? 'danger' : null),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'pagado'    => 'success',
                        'vencido'   => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'pagado'    => 'Pagado',
                        'vencido'   => 'Vencido',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'pagado'    => 'Pagado',
                        'vencido'   => 'Vencido',
                    ]),
            ])
            ->headerActions([
                Actions\CreateAction::make()->label('Agregar cuenta'),
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
            ->defaultSort('created_at', 'desc');
    }
}
