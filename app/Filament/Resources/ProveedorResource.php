<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProveedorResource\Pages;
use App\Models\Proveedor;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProveedorResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Proveedor::class;

    // Proveedores son globales: visibles en todas las sucursales
    protected static bool $isScopedToTenant = false;

    // -- Shield ----------------------------------------------------------------

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    // -- Navigation ------------------------------------------------------------

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-building-storefront';
    }

    public static function getNavigationLabel(): string
    {
        return 'Proveedores';
    }

    public static function getModelLabel(): string
    {
        return 'Proveedor';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Proveedores';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Compras';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    // -- Form ------------------------------------------------------------------

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Gestion de Proveedor')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Informacion General')
                        ->icon('heroicon-m-tag')
                        ->components([
                            Section::make('Identidad del Proveedor')
                                ->description('Datos basicos e identificacion')
                                ->icon('heroicon-m-building-storefront')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('nombre')
                                        ->label('Nombre del Proveedor')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('codigo')
                                        ->label('Codigo')
                                        ->default(function () {
                                            $last = Proveedor::where('codigo', 'like', 'PROV-%')
                                                ->orderByDesc('id')
                                                ->value('codigo');
                                            $num = $last ? ((int) substr($last, 5)) + 1 : 1;
                                            return 'PROV-' . str_pad($num, 3, '0', STR_PAD_LEFT);
                                        })
                                        ->disabled()
                                        ->dehydrated()
                                        ->required()
                                        ->unique(Proveedor::class, 'codigo', ignoreRecord: true)
                                        ->maxLength(100),

                                    Forms\Components\TextInput::make('rfc_o_nit')
                                        ->label('NIT')
                                        ->maxLength(50),
                                ]),

                            Section::make('Contacto Principal')
                                ->description('Persona de contacto en la empresa')
                                ->icon('heroicon-m-user')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('contacto_principal')
                                        ->label('Nombre del Contacto')
                                        ->required()
                                        ->maxLength(150)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('email')
                                        ->label('Correo Electronico')
                                        ->email()
                                        ->required()
                                        ->maxLength(150),

                                    Forms\Components\TextInput::make('telefono')
                                        ->label('Telefono Principal')
                                        ->tel()
                                        ->required()
                                        ->maxLength(20),

                                    Forms\Components\TextInput::make('telefono_adicional')
                                        ->label('Telefono Adicional')
                                        ->tel()
                                        ->maxLength(20),
                                ]),
                        ]),

                    Tabs\Tab::make('Ubicacion')
                        ->icon('heroicon-m-map-pin')
                        ->components([
                            Section::make('Direccion Fisica')
                                ->description('Domicilio fiscal y ubicacion')
                                ->icon('heroicon-m-home-modern')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('direccion')
                                        ->label('Direccion')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('ciudad')
                                        ->label('Ciudad / Municipio')
                                        ->required()
                                        ->maxLength(100),

                                    Forms\Components\TextInput::make('departamento')
                                        ->label('Departamento')
                                        ->required()
                                        ->maxLength(100),

                                    Forms\Components\TextInput::make('pais')
                                        ->label('Pais')
                                        ->default('El Salvador')
                                        ->required()
                                        ->maxLength(100),

                                    Forms\Components\TextInput::make('codigo_postal')
                                        ->label('Codigo Postal')
                                        ->maxLength(20),
                                ]),
                        ]),

                    Tabs\Tab::make('Condiciones Comerciales')
                        ->icon('heroicon-m-banknotes')
                        ->components([
                            Section::make('Terminos de Pago')
                                ->description('Configuracion de credito y pagos')
                                ->icon('heroicon-m-receipt-percent')
                                ->columns(2)
                                ->components([
                                    Forms\Components\Select::make('condiciones_pago')
                                        ->label('Condicion de Pago')
                                        ->options([
                                            'contado'      => 'Contado',
                                            'credito'      => 'Credito',
                                            'mixto'        => 'Mixto',
                                            'consignacion' => 'Consignacion',
                                            'adelanto'     => 'Adelanto',
                                        ])
                                        ->default('contado')
                                        ->required(),

                                    Forms\Components\TextInput::make('dias_credito')
                                        ->label('Dias de Credito')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0),

                                    Forms\Components\TextInput::make('descuento_comercial')
                                        ->label('Descuento Comercial (%)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->step(0.01)
                                        ->default(0),
                                ]),
                        ]),

                    Tabs\Tab::make('Notas y Estado')
                        ->icon('heroicon-m-document-text')
                        ->components([
                            Section::make('Informacion Adicional')
                                ->description('Notas y estado del proveedor')
                                ->icon('heroicon-m-clipboard-document-list')
                                ->components([
                                    Forms\Components\RichEditor::make('notas')
                                        ->label('Notas')
                                        ->columnSpanFull(),

                                    Forms\Components\Toggle::make('activo')
                                        ->label('Proveedor Activo')
                                        ->default(true)
                                        ->inline(),
                                ]),
                        ]),
                ]),
        ]);
    }

    // -- Table -----------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Codigo')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('contacto_principal')
                    ->label('Contacto')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->limit(35)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('condiciones_pago')
                    ->label('Pago')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'contado' => 'success',
                        'credito' => 'warning',
                        'mixto'   => 'info',
                        default   => 'gray',
                    }),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                Tables\Filters\SelectFilter::make('condiciones_pago')
                    ->label('Condicion de Pago')
                    ->options([
                        'contado'      => 'Contado',
                        'credito'      => 'Credito',
                        'mixto'        => 'Mixto',
                        'consignacion' => 'Consignacion',
                        'adelanto'     => 'Adelanto',
                    ]),
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
            ->paginated([10, 25, 50, 100]);
    }

    // -- Pages -----------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProveedores::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'view'   => Pages\ViewProveedor::route('/{record}'),
            'edit'   => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}