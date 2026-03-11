<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovimientoStockResource\Pages;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Sucursal;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;

class MovimientoStockResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = MovimientoStock::class;

    // ── Shield ────────────────────────────────────────────────────────────────

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'delete', 'delete_any'];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-arrows-right-left';
    }

    public static function getNavigationLabel(): string
    {
        return 'Movimientos';
    }

    public static function getModelLabel(): string
    {
        return 'Movimiento de stock';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Movimientos de stock';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Inventario';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Registrar movimiento')
                ->description('Los movimientos actualizan el stock del producto automáticamente')
                ->icon('heroicon-m-arrows-right-left')
                ->columns(2)
                ->components([
                    Forms\Components\Select::make('producto_id')
                        ->label('Producto')
                        ->relationship('producto', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull()
                        ->helperText('Selecciona el producto al que aplica este movimiento'),

                    Forms\Components\Select::make('tipo')
                        ->label('Tipo de movimiento')
                        ->options([
                            'entrada' => '↑ Entrada (aumenta stock)',
                            'salida'  => '↓ Salida (reduce stock)',
                            'ajuste'  => '⟳ Ajuste (establece stock exacto)',
                        ])
                        ->required()
                        ->helperText('Entrada: suma cantidad. Salida: resta cantidad. Ajuste: fija el stock al valor indicado.'),

                    Forms\Components\TextInput::make('cantidad')
                        ->label('Cantidad')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->helperText('Número de unidades del movimiento'),

                    Forms\Components\TextInput::make('precio_unitario')
                        ->label('Precio unitario')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->helperText('Opcional. Precio por unidad de este movimiento'),

                    Forms\Components\Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->options(fn () => Sucursal::where('activo', true)->pluck('nombre', 'id'))
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('referencia')
                        ->label('Referencia')
                        ->placeholder('Ej: Factura #001, Orden de compra')
                        ->maxLength(100),

                    Forms\Components\Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->placeholder('Notas adicionales sobre este movimiento...')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('user_id')
                        ->default(fn () => Auth::id()),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'salida'  => 'danger',
                        'ajuste'  => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entrada' => '↑ Entrada',
                        'salida'  => '↓ Salida',
                        'ajuste'  => '⟳ Ajuste',
                        default   => $state,
                    }),

                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->sortable()
                    ->numeric(),

                Tables\Columns\TextColumn::make('precio_unitario')
                    ->label('Precio unit.')
                    ->money('USD')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('referencia')
                    ->label('Referencia')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida'  => 'Salida',
                        'ajuste'  => 'Ajuste',
                    ]),

                Tables\Filters\SelectFilter::make('sucursal_id')
                    ->label('Sucursal')
                    ->relationship('sucursal', 'nombre'),
            ])
            ->actions([
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMovimientosStock::route('/'),
            'create' => Pages\CreateMovimientoStock::route('/create'),
        ];
    }
}
