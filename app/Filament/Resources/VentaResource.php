<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Filament\Resources\VentaResource\RelationManagers\PagosRelationManager;
use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\CobradorRecibosContador;
use App\Models\PagoVenta;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\Vendedor;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VentaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Venta::class;

    // â”€â”€ Shield â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    // â”€â”€ Navigation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shopping-bag';
    }

    public static function getNavigationLabel(): string
    {
        return 'Ventas';
    }

    public static function getModelLabel(): string
    {
        return 'Venta';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ventas';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Ventas';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    // â”€â”€ Form (usado por EditVenta â€” Tabs) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Gestión de Venta')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Cliente y Venta')
                        ->icon('heroicon-m-user-circle')
                        ->components([
                            Section::make('Identificación')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('numero_venta')
                                        ->label('Número de Venta')
                                        ->disabled()
                                        ->dehydrated(),

                                    Forms\Components\DateTimePicker::make('fecha_venta')
                                        ->label('Fecha de Venta')
                                        ->required()
                                        ->default(now()),

                                    Forms\Components\Select::make('vendedor_id')
                                        ->label('Vendedor')
                                        ->relationship('vendedor', 'nombre')
                                        ->getOptionLabelFromRecordUsing(fn (Vendedor $record) =>
                                            "{$record->nombre} {$record->apellido}"
                                        )
                                        ->searchable(['nombre', 'apellido'])
                                        ->preload()
                                        ->nullable(),
                                ]),

                            Section::make('Cliente')
                                ->icon('heroicon-m-user')
                                ->components([
                                    Forms\Components\Select::make('cliente_id')
                                        ->label('Cliente')
                                        ->relationship('cliente', 'nombre')
                                        ->getOptionLabelFromRecordUsing(fn (Cliente $record) =>
                                            "{$record->nombre} {$record->apellido}"
                                        )
                                        ->searchable(['nombre', 'apellido', 'dui'])
                                        ->preload()
                                        ->required()
                                        ->columnSpanFull()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if ($state) {
                                                $cliente = \App\Models\Cliente::find($state);
                                                if ($cliente && $cliente->limite_credito > 0) {
                                                    $set('tipo_pago', 'credito');
                                                }
                                            }
                                        }),
                                ]),

                            Section::make('Condiciones de Pago')
                                ->icon('heroicon-m-banknotes')
                                ->columns(3)
                                ->components([
                                    Forms\Components\Select::make('tipo_pago')
                                        ->label('Tipo de Pago')
                                        ->options([
                                            'contado' => 'Contado',
                                            'credito' => 'Crédito (Fiado)',
                                        ])
                                        ->default('credito')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if ($state === 'contado') {
                                                $set('dias_credito', 0);
                                                $set('fecha_pago_limite', null);
                                            }
                                        }),

                                    Forms\Components\TextInput::make('dias_credito')
                                        ->label('Días de Crédito')
                                        ->numeric()
                                        ->default(30)
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->hidden(fn (Get $get) => $get('tipo_pago') === 'contado')
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if ($state) {
                                                $set('fecha_pago_limite', Carbon::now()->addDays((int)$state)->toDateString());
                                            }
                                        }),

                                    Forms\Components\DatePicker::make('fecha_pago_limite')
                                        ->label('Fecha Límite de Pago')
                                        ->hidden(fn (Get $get) => $get('tipo_pago') === 'contado')
                                        ->default(Carbon::now()->addDays(30)),
                                ]),
                        ]),

                    Tabs\Tab::make('Productos')
                        ->icon('heroicon-m-shopping-cart')
                        ->components([
                            Section::make('Detalle de Venta')
                                ->columnSpanFull()
                                ->components([
                                    Forms\Components\Repeater::make('detalles')
                                        ->relationship('detalles')
                                        ->label('Productos')
                                        ->columns(4)
                                        ->defaultItems(1)
                                        ->addActionLabel('Agregar producto')
                                        ->schema([
                                            Forms\Components\Select::make('producto_id')
                                                ->label('Producto')
                                                ->options(
                                                    Producto::where('activo', true)
                                                        ->where('stock', '>', 0)
                                                        ->orderBy('nombre')
                                                        ->pluck('nombre', 'id')
                                                )
                                                ->searchable()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    $producto = Producto::find($state);
                                                    if ($producto) {
                                                        $set('precio_unitario', $producto->precio_venta);
                                                    }
                                                })
                                                ->columnSpan(2),

                                            Forms\Components\TextInput::make('cantidad')
                                                ->label('Cantidad')
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(1)
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $set('subtotal', round((float)$get('precio_unitario') * (float)$state * (1 - (float)$get('descuento_porcentaje') / 100), 2));
                                                }),

                                            Forms\Components\TextInput::make('precio_unitario')
                                                ->label('Precio')
                                                ->numeric()
                                                ->prefix('$')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $set('subtotal', round((float)$state * (float)$get('cantidad') * (1 - (float)$get('descuento_porcentaje') / 100), 2));
                                                }),

                                            Forms\Components\TextInput::make('descuento_porcentaje')
                                                ->label('Desc. (%)')
                                                ->numeric()
                                                ->default(0)
                                                ->suffix('%')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $set('subtotal', round((float)$get('precio_unitario') * (float)$get('cantidad') * (1 - (float)$state / 100), 2));
                                                }),

                                            Forms\Components\TextInput::make('subtotal')
                                                ->label('Subtotal')
                                                ->numeric()
                                                ->prefix('$')
                                                ->readOnly()
                                                ->default(0)
                                                ->columnSpan(2),
                                        ]),
                                ]),
                        ]),

                    Tabs\Tab::make('Totales')
                        ->icon('heroicon-m-calculator')
                        ->components([
                            Section::make('Resumen Financiero')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('subtotal')
                                        ->label('Subtotal')
                                        ->numeric()
                                        ->prefix('$')
                                        ->readOnly()
                                        ->default(0),

                                    Forms\Components\TextInput::make('descuento_porcentaje')
                                        ->label('Descuento Global (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->default(0)
                                        ->minValue(0)
                                        ->maxValue(100),

                                    Forms\Components\TextInput::make('descuento_monto')
                                        ->label('Monto Descuento')
                                        ->numeric()
                                        ->prefix('$')
                                        ->readOnly()
                                        ->default(0),

                                    Forms\Components\TextInput::make('impuesto_porcentaje')
                                        ->label('Impuesto (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->default(0)
                                        ->minValue(0),

                                    Forms\Components\TextInput::make('impuesto_monto')
                                        ->label('Monto Impuesto')
                                        ->numeric()
                                        ->prefix('$')
                                        ->readOnly()
                                        ->default(0),

                                    Forms\Components\TextInput::make('total')
                                        ->label('Total')
                                        ->numeric()
                                        ->prefix('$')
                                        ->readOnly()
                                        ->default(0),

                                    Forms\Components\TextInput::make('monto_pagado')
                                        ->label('Abono Inicial')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->minValue(0),

                                    Forms\Components\TextInput::make('saldo_pendiente')
                                        ->label('Saldo Pendiente')
                                        ->numeric()
                                        ->prefix('$')
                                        ->readOnly()
                                        ->default(0),
                                ]),
                        ]),

                    Tabs\Tab::make('Estado y Notas')
                        ->icon('heroicon-m-document-text')
                        ->components([
                            Section::make()
                                ->columns(1)
                                ->components([
                                    Forms\Components\Select::make('estado')
                                        ->label('Estado')
                                        ->options([
                                            'pendiente'  => 'Pendiente',
                                            'completada' => 'Completada',
                                            'cancelada'  => 'Cancelada',
                                            'devuelta'   => 'Devuelta',
                                        ])
                                        ->default('pendiente')
                                        ->required(),

                                    Forms\Components\Textarea::make('observaciones')
                                        ->label('Observaciones')
                                        ->rows(4)
                                        ->maxLength(1000)
                                        ->placeholder('Notas importantes sobre la venta...'),
                                ]),
                        ]),
                ]),
        ]);
    }

    // â”€â”€ Table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_venta')
                    ->label('N° Venta')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('fecha_venta')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cliente.codigo_anterior')
                    ->label('Cód.')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray')
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('cliente', fn ($q) =>
                            $q->where('codigo_anterior', 'like', "%{$search}%")
                        )
                    ),

                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) =>
                        ($record->cliente?->nombre ?? '') . ' ' . ($record->cliente?->apellido ?? '')
                    )
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('cliente', fn ($q) =>
                            $q->where('nombre', 'like', "%{$search}%")
                              ->orWhere('apellido', 'like', "%{$search}%")
                              ->orWhere('codigo_anterior', 'like', "%{$search}%")
                        )
                    )
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('tipo_pago')
                    ->label('Pago')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'contado' => 'success',
                        'credito' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'contado' => 'Contado',
                        'credito' => 'Crédito',
                        default   => $state,
                    }),

                Tables\Columns\TextColumn::make('fecha_pago_limite')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->color(fn ($record): string =>
                        $record->tipo_pago === 'credito' && $record->saldo_pendiente > 0 && $record->fecha_pago_limite && $record->fecha_pago_limite->isPast()
                            ? 'danger' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'  => 'warning',
                        'completada' => 'success',
                        'cancelada'  => 'danger',
                        'devuelta'   => 'info',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente'  => 'Pendiente',
                        'completada' => 'Completada',
                        'cancelada'  => 'Cancelada',
                        'devuelta'   => 'Devuelta',
                        default      => $state,
                    }),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo')
                    ->money('USD')
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success'),

                // ── Estado de cuotas ──────────────────────────────────────────
                Tables\Columns\TextColumn::make('estado_cuotas')
                    ->label('Cuotas')
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        if ($record->tipo_pago !== 'credito' || (float) $record->saldo_pendiente <= 0) {
                            return 'completado';
                        }
                        $info = $record->estadoCuotas();
                        return match ($info['estado']) {
                            'adelantado' => "adelantado_{$info['diferencia']}",
                            'atrasado'   => "atrasado_{$info['diferencia']}",
                            'al_dia'     => 'al_dia',
                            default      => 'completado',
                        };
                    })
                    ->formatStateUsing(function ($state, $record): string {
                        if ($record->tipo_pago !== 'credito' || (float) $record->saldo_pendiente <= 0) {
                            return '✓ Completado';
                        }
                        $info = $record->estadoCuotas();
                        return match ($info['estado']) {
                            'adelantado' => "▲ Adelantado {$info['diferencia']}",
                            'atrasado'   => "▼ Atrasado {$info['diferencia']}",
                            'al_dia'     => '● Al día',
                            default      => '✓ Completado',
                        };
                    })
                    ->color(function ($state): string {
                        if (str_starts_with($state, 'atrasado'))   return 'danger';
                        if (str_starts_with($state, 'adelantado')) return 'success';
                        if ($state === 'al_dia')                    return 'info';
                        return 'gray';
                    })
                    ->tooltip(function ($record): ?string {
                        if ($record->tipo_pago !== 'credito') return null;
                        $info   = $record->estadoCuotas();
                        $lineas = [
                            "Esperadas hoy: {$info['cuotas_esperadas']}",
                            "Cobradas: {$info['cuotas_cobradas']}",
                            "Pendientes: {$info['cuotas_pendientes']}",
                        ];
                        if ($info['parcial_numero']) {
                            $lineas[] = "Cuota #{$info['parcial_numero']} parcial: \${$info['parcial_pagado']} de \${$info['parcial_total']}";
                        }
                        if ($info['proxima_fecha']) {
                            $lineas[] = "Próxima: {$info['proxima_fecha']}";
                        }
                        return implode(' | ', $lineas);
                    }),

                Tables\Columns\TextColumn::make('vendedor.nombre')
                    ->label('Vendedor')
                    ->formatStateUsing(fn ($record) =>
                        $record->vendedor
                            ? "{$record->vendedor->nombre} {$record->vendedor->apellido}"
                            : '—'
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'completada' => 'Completada',
                        'cancelada'  => 'Cancelada',
                        'devuelta'   => 'Devuelta',
                    ]),

                Tables\Filters\SelectFilter::make('tipo_pago')
                    ->label('Tipo de Pago')
                    ->options([
                        'contado' => 'Contado',
                        'credito' => 'Crédito (Fiado)',
                    ]),

                Tables\Filters\Filter::make('con_saldo')
                    ->label('Con saldo pendiente')
                    ->query(fn (Builder $query) => $query->where('saldo_pendiente', '>', 0))
                    ->toggle(),

                Tables\Filters\Filter::make('vencidas')
                    ->label('Créditos vencidos')
                    ->query(fn (Builder $query) => $query
                        ->where('tipo_pago', 'credito')
                        ->where('saldo_pendiente', '>', 0)
                        ->where('fecha_pago_limite', '<', now())
                    )
                    ->toggle(),
            ])
            ->actions([
                Actions\Action::make('registrar_pago')
                    ->label('Pago')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->visible(fn (Venta $record): bool =>
                        $record->tipo_pago === 'credito' && (float) $record->saldo_pendiente > 0
                    )
                    ->form([
                        Forms\Components\TextInput::make('monto')
                            ->label('Monto del Pago')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0.01),
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
                            ->label('Referencia')
                            ->placeholder('Número de comprobante...'),
                    ])
                    ->action(function (Venta $record, array $data): void {
                        // Si quien registra el pago desde el panel tiene perfil de
                        // cobrador, se le genera correlativo igual que en el POS móvil
                        // — antes solo los cobros hechos desde la app tenían recibo.
                        $cobrador = Cobrador::where('user_id', auth()->id())->first();

                        PagoVenta::create([
                            'venta_id'    => $record->id,
                            'cliente_id'  => $record->cliente_id,
                            'user_id'     => auth()->id(),
                            'numero_recibo' => $cobrador ? CobradorRecibosContador::siguienteNumeroRecibo($cobrador->id) : null,
                            'monto'       => $data['monto'],
                            'fecha_pago'  => $data['fecha_pago'],
                            'metodo_pago' => $data['metodo_pago'],
                            'referencia'  => $data['referencia'] ?? null,
                        ]);
                        Notification::make()
                            ->title('Pago registrado correctamente')
                            ->success()
                            ->send();
                    }),
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha_venta', 'desc');
    }

    // â”€â”€ Relation Managers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function getRelations(): array
    {
        return [
            PagosRelationManager::class,
        ];
    }

    // â”€â”€ Pages â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVentas::route('/'),
            'create' => Pages\CreateVenta::route('/create'),
            'view'   => Pages\ViewVenta::route('/{record}'),
            'edit'   => Pages\EditVenta::route('/{record}/edit'),
        ];
    }
}
