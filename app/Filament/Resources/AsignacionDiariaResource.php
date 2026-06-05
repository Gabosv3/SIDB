<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AsignacionDiariaResource\Pages;
use App\Models\AsignacionDiaria;
use App\Models\Producto;
use App\Models\Vendedor;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AsignacionDiariaResource extends Resource
{
    protected static ?string $model = AsignacionDiaria::class;

    protected static ?string $slug = 'asignaciones-diarias';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationLabel(): string
    {
        return 'Asignaciones Diarias';
    }

    public static function getModelLabel(): string
    {
        return 'Asignación Diaria';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Asignaciones Diarias';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Ventas';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Jornada')
                ->icon('heroicon-m-calendar-days')
                ->description('Define quien llevara productos, a que sucursal pertenece y la fecha de trabajo.')
                ->columnSpanFull()
                ->columns(3)
                ->components([
                    Forms\Components\Select::make('vendedor_id')
                        ->label('Vendedor')
                        ->options(fn () => Vendedor::query()
                            ->where('activo', true)
                            ->whereNotNull('user_id')
                            ->orderBy('nombre')
                            ->get()
                            ->mapWithKeys(fn (Vendedor $v) => [
                                $v->id => "{$v->nombre} {$v->apellido}",
                            ]))
                        ->placeholder('Seleccionar vendedor...')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Solo aparecen vendedores activos con usuario del sistema vinculado.')
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $vendedor = Vendedor::whereNotNull('user_id')->find($state);
                                if ($vendedor?->sucursal_id) {
                                    $set('sucursal_id', $vendedor->sucursal_id);
                                }
                            }
                        }),

                    Forms\Components\Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->relationship('sucursal', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Se completa automaticamente al elegir vendedor.'),

                    Forms\Components\DatePicker::make('fecha')
                        ->label('Fecha de jornada')
                        ->default(today())
                        ->native(false)
                        ->required()
                        ->helperText('Usualmente es la fecha actual.'),
                ]),

            Section::make('Productos asignados')
                ->icon('heroicon-m-shopping-bag')
                ->description('Selecciona los productos que llevará el vendedor.')
                ->columnSpanFull()
                ->components([
                    Forms\Components\Repeater::make('detalles')
                        ->relationship('detalles')
                        ->label('Productos seleccionados')
                        ->columns(6)
                        ->addActionLabel('Agregar otro producto')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['producto_id'])
                            ? Producto::find($state['producto_id'])?->nombre
                            : 'Producto asignado')
                        ->schema([
                            Forms\Components\Select::make('producto_id')
                                ->label('Producto')
                                ->options(fn () => Producto::where('activo', true)
                                    ->orderBy('nombre')
                                    ->get()
                                    ->mapWithKeys(fn (Producto $producto) => [
                                        $producto->id => "{$producto->nombre} ({$producto->codigo}) - Stock: {$producto->stock}",
                                    ]))
                                ->placeholder('Buscar producto...')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $precio = Producto::find($state)?->precio_venta;
                                        $set('precio_venta', $precio);
                                    }
                                })
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('cantidad_asignada')
                                ->label('Cantidad')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('precio_venta')
                                ->label('Precio unitario')
                                ->numeric()
                                ->prefix('$')
                                ->readOnly()
                                ->columnSpan(2),
                        ]),
                ]),

            Section::make('Observaciones')
                ->icon('heroicon-m-chat-bubble-left-ellipsis')
                ->description('Notas internas para esta salida de productos.')
                ->columnSpanFull()
                ->collapsed()
                ->components([
                    Forms\Components\Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(3)
                        ->placeholder('Indicaciones, rutas sugeridas, condiciones especiales...'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('vendedor.nombre')
                    ->label('Vendedor')
                    ->formatStateUsing(fn ($record) => $record->vendedor?->nombre_completo)
                    ->searchable(['nombre', 'apellido'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('detalles_count')
                    ->label('Productos')
                    ->counts('detalles')
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'success' => 'activa',
                        'gray'    => 'liquidada',
                    ]),

                Tables\Columns\TextColumn::make('total_vendido')
                    ->label('Total vendido')
                    ->money('USD')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('total_devuelto_valor')
                    ->label('Devuelto ($)')
                    ->money('USD')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('liquidada_at')
                    ->label('Liquidada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options(['activa' => 'Activa', 'liquidada' => 'Liquidada']),

                Tables\Filters\SelectFilter::make('vendedor_id')
                    ->label('Vendedor')
                    ->relationship('vendedor', 'nombre'),

                Tables\Filters\Filter::make('hoy')
                    ->label('Solo hoy')
                    ->query(fn ($query) => $query->whereDate('fecha', today()))
                    ->default(),
            ])
            ->actions([
                Actions\Action::make('editar')
                    ->label('Editar')
                    ->icon('heroicon-m-pencil')
                    ->visible(fn (AsignacionDiaria $record) => $record->estaActiva())
                    ->url(fn (AsignacionDiaria $record) => route('asignacion-diaria.editar', [
                        'tenant' => \Filament\Facades\Filament::getTenant()->id,
                        'asignacion' => $record->id,
                    ])),

                Actions\Action::make('liquidar')
                    ->label('Liquidar jornada')
                    ->icon('heroicon-m-calculator')
                    ->color('warning')
                    ->visible(fn (AsignacionDiaria $record) => $record->estaActiva())
                    ->requiresConfirmation()
                    ->modalHeading('Liquidar jornada')
                    ->modalDescription('Se calcularán las ventas realizadas y las unidades devueltas. Esta acción no se puede deshacer.')
                    ->action(function (AsignacionDiaria $record) {
                        $record->liquidar();
                        redirect()->route('asignacion-diaria.corte', [
                            'tenant' => \Filament\Facades\Filament::getTenant()->id,
                            'asignacion' => $record->id,
                        ])->send();
                    }),

                Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAsignacionesDiarias::route('/'),
            'create' => Pages\CreateAsignacionDiaria::route('/create'),
            'edit'   => Pages\EditAsignacionDiaria::route('/{record}/edit'),
            'view'   => Pages\ViewAsignacionDiaria::route('/{record}'),
        ];
    }
}
