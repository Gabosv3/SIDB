<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Filament\Resources\ProductoResource\RelationManagers;
use App\Models\Producto;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Support\Str;
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

    // Productos son por sucursal (isScopedToTenant = true por defecto)

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
                                        ->placeholder('Ej: Refresco Cola 600ml')
                                        ->required()
                                        ->minLength(2)
                                        ->maxLength(255)
                                        ->helperText('Nombre completo tal como aparecerá en ventas y reportes.')
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('categoria_id')
                                        ->label('Categoría')
                                        ->relationship('categoria', 'nombre')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('nombre')
                                                ->label('Nombre de la categoría')
                                                ->required()
                                                ->minLength(2)
                                                ->maxLength(100),
                                            Forms\Components\Textarea::make('descripcion')
                                                ->label('Descripción')
                                                ->rows(2)
                                                ->maxLength(500),
                                        ])
                                        ->placeholder('Seleccione o cree una categoría')
                                        ->helperText('Puede crear una categoría nueva directamente aquí.'),

                                    Forms\Components\TextInput::make('codigo')
                                        ->label('Código')
                                        ->default(function () {
                                            // El máximo numérico real entre todos los códigos PROD-*, no el del
                                            // último id insertado — si hay productos borrados o códigos puestos
                                            // a mano fuera de orden, orderByDesc('id') puede repetir un número
                                            // ya usado (ej. vuelve a proponer PROD-001 si ese fue el último id
                                            // pero ya existen PROD-002, PROD-003...).
                                            $max = Producto::where('codigo', 'like', 'PROD-%')
                                                ->get(['codigo'])
                                                ->map(fn ($p) => (int) substr($p->codigo, 5))
                                                ->max();
                                            return 'PROD-' . str_pad(($max ?? 0) + 1, 3, '0', STR_PAD_LEFT);
                                        })
                                        ->disabled()
                                        ->dehydrated()
                                        ->required()
                                        ->unique(Producto::class, 'codigo', ignoreRecord: true)
                                        ->maxLength(60)
                                        ->helperText('Generado automáticamente. No puede modificarse.'),

                                    Forms\Components\Select::make('unidad_medida')
                                        ->label('Unidad de medida')
                                        ->options([
                                            'unidad'    => 'Unidad',
                                            'caja'      => 'Caja',
                                            'docena'    => 'Docena',
                                            'paquete'   => 'Paquete',
                                            'litro'     => 'Litro',
                                            'kilogramo' => 'Kilogramo',
                                            'metro'     => 'Metro',
                                        ])
                                        ->default('unidad')
                                        ->required()
                                        ->helperText('Seleccione cómo se mide o vende este producto.'),

                                    Forms\Components\Textarea::make('descripcion')
                                        ->label('Descripción')
                                        ->placeholder('Descripción breve del producto, características, presentación...')
                                        ->rows(3)
                                        ->maxLength(1000)
                                        ->helperText('Opcional. Máximo 1000 caracteres.')
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
                                        ->required()
                                        ->step(0.01)
                                        ->rules(['required', 'numeric', 'min:0'])
                                        ->helperText('Costo al que se adquiere el producto al proveedor.'),

                                    Forms\Components\TextInput::make('precio_venta')
                                        ->label('Precio de venta')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->minValue(0)
                                        ->required()
                                        ->step(0.01)
                                        ->gte('precio_compra')
                                        ->helperText('Precio al que se vende al cliente. Debe ser mayor o igual al precio de compra.'),
                                ]),

                            Section::make('Precios por cuotas')
                                ->description('Opciones de pago a plazos para este producto')
                                ->icon('heroicon-m-credit-card')
                                ->collapsible()
                                ->collapsed()
                                ->components([
                                    Forms\Components\Repeater::make('precios_cuotas')
                                        ->label('')
                                        ->addActionLabel('Agregar opción de cuotas')
                                        ->columns(3)
                                        ->defaultItems(0)
                                        ->schema([
                                            Forms\Components\TextInput::make('cuotas')
                                                ->label('N° de cuotas')
                                                ->numeric()
                                                ->minValue(2)
                                                ->maxValue(120)
                                                ->integer()
                                                ->suffix('cuotas')
                                                ->required()
                                                ->helperText('Mínimo 2 cuotas.'),

                                            Forms\Components\TextInput::make('precio_cuota')
                                                ->label('Precio por cuota')
                                                ->numeric()
                                                ->prefix('$')
                                                ->minValue(0.01)
                                                ->step(0.01)
                                                ->required()
                                                ->helperText('Monto de cada pago periódico.'),

                                            Forms\Components\TextInput::make('descripcion')
                                                ->label('Descripción')
                                                ->placeholder('Ej: Sin interés, 12 meses')
                                                ->maxLength(100)
                                                ->helperText('Nota informativa para el vendedor.'),
                                        ])
                                        ->helperText('Defina una o más opciones de financiamiento disponibles para este producto.')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Control de inventario')
                                ->description('Niveles de stock del producto')
                                ->icon('heroicon-m-clipboard-document-list')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('stock')
                                        ->label('Stock actual')
                                        ->numeric()
                                        ->integer()
                                        ->default(0)
                                        ->minValue(0)
                                        ->required()
                                        ->helperText('Cantidad de unidades disponibles en bodega.'),

                                    Forms\Components\TextInput::make('stock_minimo')
                                        ->label('Stock mínimo')
                                        ->numeric()
                                        ->integer()
                                        ->default(0)
                                        ->minValue(0)
                                        ->required()
                                        ->helperText('Se generará alerta cuando el stock sea menor o igual a este valor.'),

                                    Forms\Components\Toggle::make('activo')
                                        ->label('Producto activo')
                                        ->default(true)
                                        ->helperText('Los productos inactivos no aparecen en el punto de ventas.')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Información Adicional')
                        ->icon('heroicon-m-information-circle')
                        ->components([
                            Section::make('Dimensiones y Peso')
                                ->description('Especificaciones físicas')
                                ->icon('heroicon-m-scale')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('peso')
                                        ->label('Peso (kg)')
                                        ->numeric()
                                        ->step(0.001)
                                        ->minValue(0)
                                        ->placeholder('Ej: 0.500')
                                        ->helperText('Peso en kilogramos. Opcional, usado para cálculo de flete.'),

                                    Forms\Components\TextInput::make('dimensiones')
                                        ->label('Dimensiones')
                                        ->maxLength(100)
                                        ->placeholder('Ej: 20cm x 10cm x 5cm')
                                        ->helperText('Ancho × Alto × Profundidad. Opcional.'),
                                ]),
                        ]),

                    Tabs\Tab::make('Imagen')
                        ->icon('heroicon-m-photo')
                        ->components([
                            Section::make('Imagen del producto')
                                ->description('Fotografía o imagen representativa')
                                ->icon('heroicon-m-camera')
                                ->components([
                                    Forms\Components\FileUpload::make('imagen')
                                        ->label('Imagen')
                                        ->image()
                                        ->imageEditor()
                                        ->disk('public')
                                        ->directory('productos')
                                        ->visibility('public')
                                        ->maxSize(2048)
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->helperText('Formatos aceptados: JPG, PNG o WEBP. Tamaño máximo: 2 MB.')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Proveedores')
                        ->icon('heroicon-m-building-storefront')
                        ->components([
                            Section::make('Proveedores Disponibles')
                                ->description('Gestionar proveedores para este producto')
                                ->icon('heroicon-m-link')
                                ->columnSpanFull()
                                ->components([
                                    Forms\Components\CheckboxList::make('proveedores')
                                        ->relationship('proveedores', 'nombre')
                                        ->searchable()
                                        ->bulkToggleable()
                                        ->helperText('Seleccione todos los proveedores que suministran este producto.')
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
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('Imagen')
                    ->disk('public')
                    ->width(60)
                    ->height(60)
                    ->defaultImageUrl(asset('images/no-image.png'))
                    ->circular(false),

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

                Tables\Columns\TextColumn::make('origen')
                    ->label('Origen')
                    ->badge()
                    ->toggleable()
                    ->color(fn (string $state): string => $state === 'excel' ? 'warning' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'excel' ? 'Excel' : 'Manual'),

                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->sortable(),

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

                Tables\Filters\SelectFilter::make('origen')
                    ->label('Origen')
                    ->options(['excel' => 'Excel', 'manual' => 'Manual'])
                    ->default('manual'),
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
            ->defaultSort('nombre');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'view'   => Pages\ViewProducto::route('/{record}'),
            'edit'   => Pages\EditProducto::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VentasRelationManager::class,
        ];
    }
}
