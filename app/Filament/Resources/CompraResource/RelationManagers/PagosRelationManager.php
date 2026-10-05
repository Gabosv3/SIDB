<?php

namespace App\Filament\Resources\CompraResource\RelationManagers;

use App\Models\PagoCompra;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Pagos a proveedor de una compra. Cada pago baja el saldo pendiente y, al
 * llegar a cero, la compra pasa a Completada (lo hace PagoCompraObserver);
 * eliminar un pago devuelve el saldo.
 */
class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    protected static ?string $title = 'Pagos al proveedor';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('monto')
                ->label('Monto')
                ->numeric()
                ->prefix('$')
                ->minValue(0.01)
                ->maxValue(fn () => (float) $this->getOwnerRecord()->saldo_pendiente)
                ->helperText(fn () => 'Saldo pendiente: $' . number_format((float) $this->getOwnerRecord()->saldo_pendiente, 2))
                ->required(),
            Forms\Components\DatePicker::make('fecha_pago')
                ->label('Fecha del pago')
                ->default(now())
                ->required(),
            Forms\Components\Select::make('forma_pago')
                ->label('Forma de pago')
                ->options([
                    'efectivo' => 'Efectivo',
                    'transferencia' => 'Transferencia',
                    'cheque' => 'Cheque',
                    'tarjeta' => 'Tarjeta',
                ])
                ->default('transferencia')
                ->required(),
            Forms\Components\TextInput::make('referencia_pago')
                ->label('Referencia (N° de cheque, transferencia, etc.)')
                ->maxLength(100),
            Forms\Components\Textarea::make('observaciones')
                ->label('Notas')
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('monto')
            ->columns([
                Tables\Columns\TextColumn::make('fecha_pago')->label('Fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('monto')->label('Monto')->money('USD')->weight('semibold')->sortable(),
                Tables\Columns\TextColumn::make('forma_pago')
                    ->label('Forma de pago')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('referencia_pago')->label('Referencia')->placeholder('—'),
                Tables\Columns\TextColumn::make('usuario.name')->label('Registró')->placeholder('—'),
                Tables\Columns\TextColumn::make('observaciones')->label('Notas')->limit(40)->placeholder('—')->toggleable(),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Registrar pago')
                    ->icon('heroicon-m-banknotes')
                    ->visible(fn () => (float) $this->getOwnerRecord()->saldo_pendiente > 0
                        && ! in_array($this->getOwnerRecord()->estado, ['cancelada', 'devuelta'], true))
                    ->mutateDataUsing(function (array $data): array {
                        $data['usuario_id'] = auth()->id();

                        return $data;
                    })
                    ->after(fn () => Notification::make()->title('Pago registrado')->success()->send()),
            ])
            ->actions([
                Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->modalDescription('Al eliminar el pago, su monto vuelve al saldo pendiente de la compra.')
                    ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false),
            ])
            ->defaultSort('fecha_pago', 'desc');
    }
}
