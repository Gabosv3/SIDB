<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompraResource\Pages;
use App\Models\Compra;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompraResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Compra::class;

    // â”€â”€ Shield â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    // â”€â”€ Navigation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shopping-cart';
    }

    public static function getNavigationLabel(): string
    {
        return 'Compras';
    }

    public static function getModelLabel(): string
    {
        return 'Compra';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Compras';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Compras';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    // â”€â”€ Form (usado por EditCompra â€” con Tabs) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Gestión de Compra')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Información de Compra')
                        ->icon('heroicon-m-tag')
                        ->components([
                            Section::make('Datos Básicos')
                                ->description('Información principal de la compra')
                                ->icon('heroicon-m-document')
                                ->columns(3)
                                ->components([
                                    Forms\Components\TextInput::make('numero_compra')
                                        ->label('Número de Compra')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(fn () => Compra::generarNumeroPedido()),

                                    Forms\Components\DateTimePicker::make('fecha_compra')
                                        ->label('Fecha de Compra')
                                        ->required()
                                        ->default(now()),

                                    Forms\Components\Select::make('usuario_id')
                                        ->label('Usuario')
                                        ->relationship('usuario', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload(),
                                ]),

                            Section::make('Proveedor')
                                ->description('Seleccionar proveedor')
                                ->icon('heroicon-m-building-storefront')
                                ->components([
                                    Forms\Components\Select::make('proveedor_id')
                                        ->label('Proveedor')
                                        ->relationship('proveedor', 'nombre')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Fechas')
                                ->description('Gestión de fechas de entrega')
                                ->icon('heroicon-m-calendar')
                                ->columns(2)
                                ->components([
                                    Forms\Components\DatePicker::make('fecha_entrega_estimada')
                                        ->label('Fecha Entrega Estimada'),

                                    Forms\Components\DatePicker::make('fecha_entrega_real')
                                        ->label('Fecha Entrega Real'),
                                ]),
                        ]),

                    Tabs\Tab::make('Artículos')
                        ->icon('heroicon-m-inbox-stack')
                        ->components([
                            Section::make('Detalle de Compra')
                                ->description('Productos incluidos en la compra')
                                ->icon('heroicon-m-list-bullet')
                                ->columnSpanFull()
                                ->components([
                                    Forms\Components\Repeater::make('detalles')
                                        ->relationship()
                                        ->label('Artículos')
                                        ->addActionLabel('Añadir Artículo')
                                        ->reorderable()
                                        ->collapsible()
                                        ->cloneable()
                                        ->columns(2)
                                        ->schema([
                                            Forms\Components\Select::make('producto_id')
                                                ->label('Producto')
                                                ->relationship('producto', 'nombre')
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->columnSpanFull()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    if ($state) {
                                                        $producto = \App\Models\Producto::find($state);
                                                        if ($producto) {
                                                            $set('precio_unitario', $producto->precio_compra);
                                                        }
                                                    }
                                                }),

                                            Forms\Components\TextInput::make('cantidad')
                                                ->label('Cantidad')
                                                ->numeric()
                                                ->required()
                                                ->minValue(1)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $set('subtotal', ((float)$get('precio_unitario') - (float)$get('descuento_unitario')) * (int)$state);
                                                }),

                                            Forms\Components\TextInput::make('precio_unitario')
                                                ->label('Precio Unitario')
                                                ->numeric()
                                                ->required()
                                                ->step(0.01)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $set('subtotal', ((float)$state - (float)$get('descuento_unitario')) * (int)$get('cantidad'));
                                                }),

                                            Forms\Components\TextInput::make('descuento_unitario')
                                                ->label('Descuento Unitario')
                                                ->numeric()
                                                ->step(0.01)
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $set('subtotal', ((float)$get('precio_unitario') - (float)$state) * (int)$get('cantidad'));
                                                }),

                                            Forms\Components\TextInput::make('subtotal')
                                                ->label('Subtotal')
                                                ->numeric()
                                                ->disabled()
                                                ->dehydrated()
                                                ->default(0),

                                            Forms\Components\TextInput::make('numero_lote')
                                                ->label('Número de Lote')
                                                ->maxLength(100),

                                            Forms\Components\DatePicker::make('fecha_vencimiento')
                                                ->label('Fecha Vencimiento Producto'),

                                            Forms\Components\Textarea::make('observaciones')
                                                ->label('Observaciones')
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                        ]),

                    Tabs\Tab::make('Totales y Pago')
                        ->icon('heroicon-m-banknotes')
                        ->components([
                            Section::make('Cálculos')
                                ->description('Resumen de totales')
                                ->icon('heroicon-m-calculator')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('subtotal')
                                        ->label('Subtotal')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0),

                                    Forms\Components\TextInput::make('descuento_monto')
                                        ->label('Descuento ($)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::actualizarTotal($get, $set)),

                                    Forms\Components\TextInput::make('impuesto_porcentaje')
                                        ->label('Impuesto (%)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::actualizarTotal($get, $set)),

                                    Forms\Components\TextInput::make('impuesto_monto')
                                        ->label('Monto Impuesto')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0),

                                    Forms\Components\TextInput::make('total')
                                        ->label('Total Final')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0),
                                ]),

                            Section::make('Términos de Pago')
                                ->description('Condiciones y formas de pago')
                                ->icon('heroicon-m-credit-card')
                                ->columns(2)
                                ->components([
                                    Forms\Components\Select::make('forma_pago')
                                        ->label('Forma de Pago')
                                        ->options([
                                            'efectivo'      => 'Efectivo',
                                            'transferencia' => 'Transferencia',
                                            'cheque'        => 'Cheque',
                                            'tarjeta'       => 'Tarjeta',
                                            'credito'       => 'Crédito',
                                        ])
                                        ->default('credito')
                                        ->required(),

                                    Forms\Components\Select::make('condicion_pago')
                                        ->label('Condición de Pago')
                                        ->options([
                                            'contado' => 'Contado',
                                            'credito' => 'Crédito',
                                            'mixta'   => 'Mixta',
                                        ])
                                        ->default('contado')
                                        ->required(),

                                    Forms\Components\TextInput::make('dias_credito')
                                        ->label('Días de Crédito')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if ($state) {
                                                $set('fecha_vencimiento', Carbon::now()->addDays($state)->toDateString());
                                            }
                                        }),

                                    Forms\Components\DatePicker::make('fecha_vencimiento')
                                        ->label('Fecha Vencimiento Pago'),
                                ]),

                            Section::make('Estado')
                                ->description('Estado de la compra')
                                ->icon('heroicon-m-check-circle')
                                ->columns(2)
                                ->components([
                                    Forms\Components\Select::make('estado')
                                        ->label('Estado')
                                        ->options([
                                            'pendiente'  => 'Pendiente',
                                            'recibida'   => 'Recibida',
                                            'completada' => 'Completada',
                                            'cancelada'  => 'Cancelada',
                                            'devuelta'   => 'Devuelta',
                                        ])
                                        ->default('pendiente')
                                        ->required(),

                                    Forms\Components\TextInput::make('saldo_pendiente')
                                        ->label('Saldo Pendiente')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0),
                                ]),
                        ]),

                    Tabs\Tab::make('Observaciones')
                        ->icon('heroicon-m-document-text')
                        ->components([
                            Section::make()
                                ->components([
                                    Forms\Components\Textarea::make('observaciones')
                                        ->label('Observaciones Generales')
                                        ->placeholder('Agregue notas importantes sobre la compra...')
                                        ->columnSpanFull()
                                        ->rows(6),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function actualizarTotal($get, $set): void
    {
        $subtotal           = (float)($get('subtotal') ?? 0);
        $descuento          = (float)($get('descuento_monto') ?? 0);
        $impuestoPorcentaje = (float)($get('impuesto_porcentaje') ?? 0);
        $subtotalConDesc    = $subtotal - $descuento;
        $impuestoMonto      = ($subtotalConDesc * $impuestoPorcentaje) / 100;
        $total              = $subtotalConDesc + $impuestoMonto;

        $set('impuesto_monto', round($impuestoMonto, 2));
        $set('total', round($total, 2));
        $set('saldo_pendiente', round($total, 2));
    }

    // â”€â”€ Table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_compra')
                    ->label('Compra')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('fecha_compra')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo')
                    ->money('USD')
                    ->color(fn (string $state): string =>
                        (float)$state > 0 ? 'danger' : 'success'
                    ),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'  => 'warning',
                        'recibida'   => 'info',
                        'completada' => 'success',
                        'cancelada'  => 'danger',
                        'devuelta'   => 'gray',
                        default      => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Registrado por')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'recibida'   => 'Recibida',
                        'completada' => 'Completada',
                        'cancelada'  => 'Cancelada',
                        'devuelta'   => 'Devuelta',
                    ]),

                Tables\Filters\SelectFilter::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('con_deuda')
                    ->label('Compras con Deuda')
                    ->query(fn (Builder $query): Builder => $query->where('saldo_pendiente', '>', 0)),

                Tables\Filters\Filter::make('ultimos_30_dias')
                    ->label('Últimos 30 días')
                    ->query(fn (Builder $query): Builder => $query->where('fecha_compra', '>=', Carbon::now()->subDays(30))),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('fecha_compra', 'desc');
    }

    // â”€â”€ Pages â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompras::route('/'),
            'create' => Pages\CreateCompra::route('/create'),
            'view'   => Pages\ViewCompra::route('/{record}'),
            'edit'   => Pages\EditCompra::route('/{record}/edit'),
        ];
    }
}

