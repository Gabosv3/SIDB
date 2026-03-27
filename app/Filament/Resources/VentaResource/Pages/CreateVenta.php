<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\Producto;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;

class CreateVenta extends CreateRecord
{
    use HasWizard;

    protected static string $resource = VentaResource::class;

    protected static ?string $title = 'Nueva Venta';

    public function getSteps(): array
    {
        return [
            // ── Paso 1: Cliente ───────────────────────────────────────────────
            Step::make('Cliente')
                ->icon('heroicon-m-user-circle')
                ->description('Seleccione el cliente y condiciones de pago')
                ->components([
                    Section::make('Datos de la Venta')
                        ->columns(2)
                        ->components([
                            Forms\Components\DateTimePicker::make('fecha_venta')
                                ->label('Fecha de Venta')
                                ->required()
                                ->default(now()),

                            Forms\Components\Select::make('usuario_id')
                                ->label('Vendedor')
                                ->relationship('user', 'name')
                                ->default(fn () => auth()->id())
                                ->searchable()
                                ->preload(),
                        ]),

                    Section::make('Cliente')
                        ->icon('heroicon-m-user')
                        ->components([
                            Forms\Components\Select::make('cliente_id')
                                ->label('Cliente')
                                ->relationship('cliente', 'nombre')
                                ->getOptionLabelFromRecordUsing(fn (\App\Models\Cliente $record) =>
                                    "{$record->nombre} {$record->apellido}"
                                    . ($record->saldo > 0 ? " — Saldo: \${$record->saldo}" : '')
                                )
                                ->searchable(['nombre', 'apellido', 'dui'])
                                ->preload()
                                ->required()
                                ->columnSpanFull()
                                ->live()
                                ->helperText('Si el cliente tiene saldo pendiente se mostrará en la lista'),
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
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state === 'contado') {
                                        $set('dias_credito', 0);
                                        $set('fecha_pago_limite', null);
                                    } else {
                                        $set('dias_credito', 30);
                                        $set('fecha_pago_limite', Carbon::now()->addDays(30)->toDateString());
                                    }
                                }),

                            Forms\Components\TextInput::make('dias_credito')
                                ->label('Días de Crédito')
                                ->numeric()
                                ->default(30)
                                ->minValue(0)
                                ->live(onBlur: true)
                                ->hidden(fn (Forms\Get $get) => $get('tipo_pago') === 'contado')
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $set('fecha_pago_limite', Carbon::now()->addDays((int)$state)->toDateString());
                                    }
                                }),

                            Forms\Components\DatePicker::make('fecha_pago_limite')
                                ->label('Fecha Límite de Pago')
                                ->hidden(fn (Forms\Get $get) => $get('tipo_pago') === 'contado')
                                ->default(Carbon::now()->addDays(30)),
                        ]),
                ]),

            // ── Paso 2: Productos ─────────────────────────────────────────────
            Step::make('Productos')
                ->icon('heroicon-m-shopping-cart')
                ->description('Agregue los productos de la venta')
                ->components([
                    Section::make('Artículos')
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
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $producto = Producto::find($state);
                                            if ($producto) {
                                                $set('precio_unitario', $producto->precio_venta);
                                                $set('subtotal', $producto->precio_venta);
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
                                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                            $set('subtotal', round((float)$get('precio_unitario') * (float)$state * (1 - (float)$get('descuento_porcentaje') / 100), 2));
                                        }),

                                    Forms\Components\TextInput::make('precio_unitario')
                                        ->label('Precio')
                                        ->numeric()
                                        ->prefix('$')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                            $set('subtotal', round((float)$state * (float)$get('cantidad') * (1 - (float)$get('descuento_porcentaje') / 100), 2));
                                        }),

                                    Forms\Components\TextInput::make('descuento_porcentaje')
                                        ->label('Desc. (%)')
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('%')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
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

            // ── Paso 3: Totales ───────────────────────────────────────────────
            Step::make('Totales')
                ->icon('heroicon-m-calculator')
                ->description('Revise los totales y el abono inicial')
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
                                ->default(0),

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
                                ->default(0),

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
                                ->label('Abono Inicial ($)')
                                ->numeric()
                                ->prefix('$')
                                ->default(0)
                                ->minValue(0)
                                ->helperText('Deje en 0 si es fiado completo'),

                            Forms\Components\TextInput::make('saldo_pendiente')
                                ->label('Saldo Pendiente')
                                ->numeric()
                                ->prefix('$')
                                ->readOnly()
                                ->default(0),
                        ]),
                ]),

            // ── Paso 4: Confirmar ─────────────────────────────────────────────
            Step::make('Confirmar')
                ->icon('heroicon-m-check-circle')
                ->description('Estado de la venta y observaciones finales')
                ->components([
                    Section::make('Estado')
                        ->icon('heroicon-m-check-circle')
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
                                ->placeholder('Notas sobre la venta, acuerdos de pago, etc...'),
                        ]),
                ]),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}