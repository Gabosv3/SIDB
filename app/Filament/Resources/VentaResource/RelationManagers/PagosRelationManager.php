<?php

namespace App\Filament\Resources\VentaResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
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
        return $schema->components([
            Forms\Components\TextInput::make('monto')
                ->label('Monto del Pago')
                ->numeric()
                ->prefix('$')
                ->required()
                ->minValue(0.01)
                ->columnSpanFull(),

            Forms\Components\DatePicker::make('fecha_pago')
                ->label('Fecha de Pago')
                ->required()
                ->default(today()),

            Forms\Components\Select::make('metodo_pago')
                ->label('Método de Pago')
                ->options([
                    'efectivo'      => 'Efectivo',
                    'transferencia' => 'Transferencia',
                    'cheque'        => 'Cheque',
                    'deposito'      => 'Depósito',
                ])
                ->default('efectivo')
                ->required(),

            Forms\Components\TextInput::make('referencia')
                ->label('Referencia / N° de transacción')
                ->placeholder('Ej: TRF-00123')
                ->maxLength(100),

            Forms\Components\Textarea::make('observaciones')
                ->label('Observaciones')
                ->rows(3)
                ->maxLength(500)
                ->columnSpanFull(),
        ]);
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
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Registrar Pago')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id']    = auth()->id();
                        $data['cliente_id'] = $this->getOwnerRecord()->cliente_id;
                        return $data;
                    }),
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
            ->defaultSort('fecha_pago', 'desc');
    }
}