<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PreventaResource\Pages;
use App\Models\Preventa;
use App\Models\Vendedor;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PreventaResource extends Resource
{
    protected static ?string $model = Preventa::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shopping-bag';
    }

    public static function getNavigationLabel(): string
    {
        return 'Preventas';
    }

    public static function getModelLabel(): string
    {
        return 'Preventa';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Preventas';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Ventas';
    }

    public static function getNavigationSort(): ?int
    {
        return 15;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Preventa::where('estado', 'pendiente')->whereNull('vendedor_id')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Preventas sin vendedor asignado';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Preventa')
                ->description('Registrada por el cobrador desde la calle. El monto es estimado con el precio de catálogo.')
                ->columns(2)
                ->components([
                    Forms\Components\Select::make('cliente_id')
                        ->label('Cliente')
                        ->relationship('cliente', 'nombre')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\Select::make('user_id')
                        ->label('Registrada por (cobrador)')
                        ->relationship('user', 'name')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('monto_estimado')
                        ->label('Monto estimado')
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\DatePicker::make('fecha')
                        ->label('Fecha')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options([
                            'pendiente'  => 'Pendiente',
                            'convertida' => 'Convertida en venta',
                            'rechazada'  => 'Rechazada',
                        ])
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\Hidden::make('sucursal_id'),
                ]),

            Section::make('Productos solicitados')
                ->components([
                    Forms\Components\Repeater::make('detalles')
                        ->relationship('detalles')
                        ->label('')
                        ->columns(4)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Forms\Components\Select::make('producto_id')
                                ->label('Producto')
                                ->relationship('producto', 'nombre')
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(),
                        ]),
                ]),

            Section::make('Asignación y observaciones')
                ->columns(2)
                ->components([
                    Forms\Components\Select::make('vendedor_id')
                        ->label('Vendedor asignado')
                        ->options(fn (Get $get) => Vendedor::where('activo', true)
                            ->when($get('sucursal_id'), fn ($q, $sucursalId) => $q->where('sucursal_id', $sucursalId))
                            ->orderBy('nombre')
                            ->get()
                            ->mapWithKeys(fn ($v) => [(string) $v->id => "{$v->nombre} {$v->apellido} ({$v->codigo})"]))
                        ->searchable()
                        ->nullable(),

                    Forms\Components\Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) => $record->cliente?->nombre_completo)
                    ->searchable(query: fn ($query, string $search) => $query->whereHas('cliente', fn ($q) => $q
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                    )),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cobrador')
                    ->searchable(),

                Tables\Columns\TextColumn::make('detalles')
                    ->label('Productos')
                    ->formatStateUsing(fn ($record) => $record->detalles
                        ->map(fn ($d) => "{$d->cantidad}x {$d->producto?->nombre}")
                        ->join(', '))
                    ->wrap()
                    ->limit(80),

                Tables\Columns\TextColumn::make('monto_estimado')
                    ->label('Monto est.')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendedor.nombre')
                    ->label('Vendedor asignado')
                    ->formatStateUsing(fn ($record) => $record->vendedor
                        ? $record->vendedor->nombre . ' ' . $record->vendedor->apellido
                        : '— Sin asignar')
                    ->color(fn ($record) => $record->vendedor ? null : 'danger'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'  => 'warning',
                        'convertida' => 'success',
                        'rechazada'  => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente'  => 'Pendiente',
                        'convertida' => 'Convertida',
                        'rechazada'  => 'Rechazada',
                        default      => $state,
                    }),

                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'convertida' => 'Convertida',
                        'rechazada'  => 'Rechazada',
                    ])
                    ->default('pendiente'),

                Tables\Filters\Filter::make('sin_asignar')
                    ->label('Sin vendedor asignado')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNull('vendedor_id')),
            ])
            ->actions([
                Actions\Action::make('convertirEnVenta')
                    ->label('Convertir en venta')
                    ->icon('heroicon-m-shopping-cart')
                    ->color('success')
                    ->visible(fn (Preventa $record) => $record->estado === 'pendiente' && $record->vendedor_id !== null)
                    ->url(fn (Preventa $record) => VentaResource::getUrl('create', ['preventa' => $record->id])),

                Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Preventa $record) => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo del rechazo')
                            ->rows(2),
                    ])
                    ->action(function (Preventa $record, array $data) {
                        $record->update([
                            'estado'        => 'rechazada',
                            'observaciones' => trim(($record->observaciones ? $record->observaciones . ' | ' : '') . 'Rechazada: ' . ($data['motivo'] ?? '')),
                        ]);

                        Notification::make()
                            ->title('Preventa rechazada')
                            ->warning()
                            ->send();
                    }),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPreventas::route('/'),
            'edit'  => Pages\EditPreventa::route('/{record}/edit'),
        ];
    }
}
