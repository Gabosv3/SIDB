<?php

namespace App\Filament\Resources\CompraResource\Pages;

use App\Filament\Resources\CompraResource;
use App\Models\Compra;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;

class CreateCompra extends CreateRecord
{
    use HasWizard;

    protected static string $resource = CompraResource::class;

    protected static ?string $title = 'Nueva Compra';

    public function getSteps(): array
    {
        return [
            // â”€â”€ Paso 1: Proveedor â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            Step::make('Proveedor')
                ->icon('heroicon-m-building-storefront')
                ->description('Proveedor, fechas y responsable')
                ->components([
                    Section::make('Identificación')
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
                                ->label('Responsable')
                                ->relationship('usuario', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->default(fn () => auth()->id()),
                        ]),

                    Section::make('Proveedor')
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

                    Section::make('Fechas de Entrega')
                        ->icon('heroicon-m-calendar')
                        ->columns(2)
                        ->components([
                            Forms\Components\DatePicker::make('fecha_entrega_estimada')
                                ->label('Fecha Entrega Estimada'),

                            Forms\Components\DatePicker::make('fecha_entrega_real')
                                ->label('Fecha Entrega Real'),
                        ]),
                ]),

            // â”€â”€ Paso 2: ArtÃ­culos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            Step::make('Artículos')
                ->icon('heroicon-m-inbox-stack')
                ->description('Productos incluidos en la compra')
                ->components([
                    Section::make('Detalle de Artículos')
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
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularTotalesDesdeDetalles($get, $set))
                                ->deleteAction(fn ($action) => $action->after(fn (Get $get, Set $set) => self::recalcularTotalesDesdeDetalles($get, $set)))
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

                                    Forms\Components\Textarea::make('observaciones')
                                        ->label('Observaciones')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),

            // â”€â”€ Paso 3: Totales y Pago â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            Step::make('Totales y Pago')
                ->icon('heroicon-m-banknotes')
                ->description('Montos, impuestos y condiciones de pago')
                ->components([
                    Section::make('Cálculos')
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
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $subtotal = (float)($get('subtotal') ?? 0);
                                    $descuento = (float)($get('descuento_monto') ?? 0);
                                    $pct = (float)($get('impuesto_porcentaje') ?? 0);
                                    $base = $subtotal - $descuento;
                                    $imp = ($base * $pct) / 100;
                                    $set('impuesto_monto', round($imp, 2));
                                    $set('total', round($base + $imp, 2));
                                    $set('saldo_pendiente', round($base + $imp, 2));
                                }),

                            Forms\Components\TextInput::make('impuesto_porcentaje')
                                ->label('Impuesto (%)')
                                ->numeric()
                                ->step(0.01)
                                ->default(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $subtotal = (float)($get('subtotal') ?? 0);
                                    $descuento = (float)($get('descuento_monto') ?? 0);
                                    $pct = (float)($get('impuesto_porcentaje') ?? 0);
                                    $base = $subtotal - $descuento;
                                    $imp = ($base * $pct) / 100;
                                    $set('impuesto_monto', round($imp, 2));
                                    $set('total', round($base + $imp, 2));
                                    $set('saldo_pendiente', round($base + $imp, 2));
                                }),

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
                                        $set('fecha_vencimiento', Carbon::now()->addDays((int)$state)->toDateString());
                                    }
                                }),

                            Forms\Components\DatePicker::make('fecha_vencimiento')
                                ->label('Fecha Vencimiento Pago'),
                        ]),
                ]),

            // â”€â”€ Paso 4: Finalizar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            Step::make('Finalizar')
                ->icon('heroicon-m-check-circle')
                ->description('Estado y observaciones finales')
                ->components([
                    Section::make('Estado de la Compra')
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

                    Section::make('Observaciones')
                        ->icon('heroicon-m-document-text')
                        ->components([
                            Forms\Components\Textarea::make('observaciones')
                                ->label('Notas de la Compra')
                                ->placeholder('Agregue notas importantes sobre la compra...')
                                ->columnSpanFull()
                                ->rows(5),
                        ]),
                ]),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['numero_compra'] = Compra::generarNumeroPedido();
        $data['usuario_id']    = auth()->id();

        return $data;
    }

    /**
     * El subtotal de cada línea del repeater se calculaba bien, pero nunca se
     * sumaba al "Subtotal" general del paso "Totales y Pago" — se quedaba
     * siempre en 0 porque nada lo recalculaba a partir de las líneas.
     */
    private static function recalcularTotalesDesdeDetalles(Get $get, Set $set): void
    {
        $detalles = $get('detalles') ?? [];
        $subtotal = collect($detalles)->sum(fn ($fila) => (float) ($fila['subtotal'] ?? 0));

        $descuento = (float) ($get('descuento_monto') ?? 0);
        $pct = (float) ($get('impuesto_porcentaje') ?? 0);
        $base = $subtotal - $descuento;
        $impuesto = ($base * $pct) / 100;

        $set('subtotal', round($subtotal, 2));
        $set('impuesto_monto', round($impuesto, 2));
        $set('total', round($base + $impuesto, 2));
        $set('saldo_pendiente', round($base + $impuesto, 2));
    }
}

