<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Models\Producto;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class ProductoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Producto::class;

    // ── Shield ────────────────────────────────────────────────────────────────

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-archive-box';
    }

    public static function getNavigationLabel(): string
    {
        return 'Productos';
    }

    public static function getModelLabel(): string
    {
        return 'Producto';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Productos';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Inventario';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Gestión de producto')
            ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Información del producto')
                        ->icon('heroicon-m-tag')
                        ->components([
                            Section::make('Datos básicos')
                                ->description('Identificación del producto')
                                ->icon('heroicon-m-cube')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('nombre')
                                        ->label('Nombre del producto')
                                        ->placeholder('Ej: Aceite de motor 20W-50')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('codigo')
                                        ->label('Código')
                                        ->placeholder('Ej: PROD-001')
                                        ->required()
                                        ->unique(Producto::class, 'codigo', ignoreRecord: true)
                                        ->maxLength(60)
                                        ->helperText('Código único del producto'),

                                    Forms\Components\Select::make('unidad_medida')
                                        ->label('Unidad de medida')
                                        ->options([
                                            'unidad'   => 'Unidad',
                                            'caja'     => 'Caja',
                                            'docena'   => 'Docena',
                                            'paquete'  => 'Paquete',
                                            'litro'    => 'Litro',
                                            'kilogramo'=> 'Kilogramo',
                                            'metro'    => 'Metro',
                                        ])
                                        ->default('unidad')
                                        ->required(),

                                    Forms\Components\Textarea::make('descripcion')
                                        ->label('Descripción')
                                        ->placeholder('Describe el producto...')
                                        ->rows(3)
                                        ->maxLength(1000)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Precios y stock')
                        ->icon('heroicon-m-currency-dollar')
                        ->components([
                            Section::make('Precios')
                                ->description('Costos y precios de venta')
                                ->icon('heroicon-m-banknotes')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('precio_compra')
                                        ->label('Precio de compra')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->minValue(0)
                                        ->helperText('Costo de adquisición'),

                                    Forms\Components\TextInput::make('precio_venta')
                                        ->label('Precio de venta')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->minValue(0)
                                        ->helperText('Precio al que se vende al cliente'),
                                ]),

                            Section::make('Control de inventario')
                                ->description('Niveles de stock del producto')
                                ->icon('heroicon-m-clipboard-document-list')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('stock')
                                        ->label('Stock actual')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->helperText('Cantidad disponible en bodega'),

                                    Forms\Components\TextInput::make('stock_minimo')
                                        ->label('Stock mínimo')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->helperText('Alerta cuando el stock baje de este nivel'),

                                    Forms\Components\Toggle::make('activo')
                                        ->label('Producto activo')
                                        ->default(true)
                                        ->helperText('Los productos inactivos no aparecen en las ventas.')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('unidad_medida')
                    ->label('Unidad')
                    ->badge(),

                Tables\Columns\TextColumn::make('precio_compra')
                    ->label('Compra')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('precio_venta')
                    ->label('Venta')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (Producto $record): string => $record->stockBajo() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('stock_minimo')
                    ->label('Mínimo')
                    ->sortable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                Tables\Filters\Filter::make('stock_bajo')
                    ->label('Stock bajo')
                    ->query(fn ($query) => $query->whereColumn('stock', '<=', 'stock_minimo'))
                    ->toggle(),
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
            ->defaultSort('nombre');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'edit'   => Pages\EditProducto::route('/{record}/edit'),
        ];
    }
}
