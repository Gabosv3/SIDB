<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\Preventa;
use App\Models\Producto;
use App\Models\Vendedor;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;

class CreateVenta extends CreateRecord
{
    use HasWizard;

    protected static string $resource = VentaResource::class;

    protected static ?string $title = 'Nueva Venta';

    /** Preventa de origen cuando se llega vía "Convertir en venta". */
    protected ?Preventa $sourcePreventa = null;

    public function mount(): void
    {
        parent::mount();

        $preventaId = request()->query('preventa');

        if (! $preventaId) {
            return;
        }

        $preventa = Preventa::with('detalles')->find($preventaId);

        if (! $preventa || $preventa->estado !== 'pendiente') {
            return;
        }

        $this->sourcePreventa = $preventa;

        $this->form->fill([
            'cliente_id'    => $preventa->cliente_id,
            'vendedor_id'   => $preventa->vendedor_id,
            'observaciones' => $preventa->observaciones,
            'detalles'      => $preventa->detalles->map(fn ($d) => [
                'producto_id'          => $d->producto_id,
                'cantidad'             => $d->cantidad,
                'precio_unitario'      => (float) $d->precio_unitario,
                'descuento_porcentaje' => 0,
                'subtotal'             => (float) $d->subtotal,
            ])->toArray(),
        ]);
    }

    public function getSteps(): array
    {
        return [
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

                            Forms\Components\Select::make('vendedor_id')
                                ->label('Vendedor')
                                ->relationship('vendedor', 'nombre')
                                ->getOptionLabelFromRecordUsing(fn (\App\Models\Vendedor $record) =>
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
                        ->columns(4)
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
                                        $set('plazo', 0);
                                        $set('unidad_plazo', 'dias');
                                        $set('dias_credito', 0);
                                        $set('fecha_pago_limite', null);
                                    } else {
                                        $set('plazo', 30);
                                        $set('unidad_plazo', 'dias');
                                        $set('dias_credito', 30);
                                        $set('fecha_pago_limite', Carbon::now()->addDays(30)->toDateString());
                                    }
                                }),

                            Forms\Components\TextInput::make('plazo')
                                ->label('Plazo')
                                ->numeric()
                                ->default(30)
                                ->minValue(1)
                                ->hidden(fn (Get $get) => $get('tipo_pago') === 'contado')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $unidad = $get('unidad_plazo') ?? 'dias';
                                    $dias = $unidad === 'meses' ? (int)$state * 30 : (int)$state;
                                    $set('dias_credito', $dias);
                                    $set('fecha_pago_limite', Carbon::now()->addDays($dias)->toDateString());
                                }),

                            Forms\Components\Select::make('unidad_plazo')
                                ->label('Unidad')
                                ->options([
                                    'dias'  => 'Días',
                                    'meses' => 'Meses',
                                ])
                                ->default('dias')
                                ->hidden(fn (Get $get) => $get('tipo_pago') === 'contado')
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $plazo = (int)($get('plazo') ?? 30);
                                    $dias = $state === 'meses' ? $plazo * 30 : $plazo;
                                    $set('dias_credito', $dias);
                                    $set('fecha_pago_limite', Carbon::now()->addDays($dias)->toDateString());
                                }),

                            Forms\Components\DatePicker::make('fecha_pago_limite')
                                ->label('Fecha Límite de Pago')
                                ->hidden(fn (Get $get) => $get('tipo_pago') === 'contado')
                                ->default(Carbon::now()->addDays(30)),
                        ]),
                ]),

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
                                        ->afterStateUpdated(function ($state, Set $set) {
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
        // dias_credito ya viene calculado desde el live update
        unset($data['plazo'], $data['unidad_plazo']);
        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->sourcePreventa) {
            return;
        }

        $this->sourcePreventa->update([
            'estado'   => 'convertida',
            'venta_id' => $this->getRecord()->id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}