<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers\CuentasCobrarRelationManager;
use App\Filament\Resources\ClienteResource\RelationManagers\VentasRelationManager;
use App\Filament\Resources\ClienteResource\RelationManagers\PagosVentaRelationManager;
use App\Models\Cliente;
use App\Models\Cobrador;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Cliente::class;

    // ── Shield ────────────────────────────────────────────────────────────────

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationLabel(): string
    {
        return 'Clientes';
    }

    public static function getModelLabel(): string
    {
        return 'Cliente';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Clientes';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Comercial';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sucursal')
                ->description('La sucursal a la que pertenece este cliente')
                ->icon('heroicon-m-building-storefront')
                ->hidden(fn (string $operation) => $operation === 'create')
                ->components([
                    Forms\Components\Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->relationship('sucursal', 'nombre')
                        ->disabled()
                        ->dehydrated(),
                ]),

            Tabs::make('Gestión de cliente')
                ->columnSpanFull()
                ->tabs([
                    // ── Tab 1: Información personal ───────────────────────────
                    Tabs\Tab::make('Información personal')
                        ->icon('heroicon-m-user-circle')
                        ->components([
                            Section::make('Datos personales')
                                ->description('Identificación del cliente')
                                ->icon('heroicon-m-identification')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('nombre')
                                        ->label('Nombre(s)')
                                        ->placeholder('Ej: Juan Carlos')
                                        ->required()
                                        ->maxLength(100),

                                    Forms\Components\TextInput::make('apellido')
                                        ->label('Apellido(s)')
                                        ->placeholder('Ej: González Pérez')
                                        ->required()
                                        ->maxLength(100),

                                    Forms\Components\TextInput::make('nombre_conyuge')
                                        ->label('Nombre del cónyuge')
                                        ->placeholder('Ej: María Pérez')
                                        ->maxLength(200)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('dui')
                                        ->label('DUI')
                                        ->placeholder('00000000-0')
                                        ->unique(Cliente::class, 'dui', ignoreRecord: true)
                                        ->maxLength(15)
                                        ->helperText('Documento Único de Identidad'),

                                    Forms\Components\TextInput::make('nit')
                                        ->label('NIT')
                                        ->placeholder('0000-000000-000-0')
                                        ->unique(Cliente::class, 'nit', ignoreRecord: true)
                                        ->maxLength(20)
                                        ->helperText('Número de Identificación Tributaria'),
                                ]),

                            Section::make('Fotografías del DUI')
                                ->description('Sube una foto clara de ambos lados del DUI')
                                ->icon('heroicon-m-camera')
                                ->columns(2)
                                ->components([
                                    Forms\Components\FileUpload::make('dui_foto_frente')
                                        ->label('DUI — Frente')
                                        ->image()
                                        ->imageEditor()
                                        ->imagePreviewHeight('180')
                                        ->uploadingMessage('Subiendo...')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->maxSize(4096)
                                        ->directory('dui/frente')
                                        ->visibility('private')
                                        ->helperText('JPG, PNG o WEBP, máx. 4 MB'),

                                    Forms\Components\FileUpload::make('dui_foto_reverso')
                                        ->label('DUI — Reverso')
                                        ->image()
                                        ->imageEditor()
                                        ->imagePreviewHeight('180')
                                        ->uploadingMessage('Subiendo...')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->maxSize(4096)
                                        ->directory('dui/reverso')
                                        ->visibility('private')
                                        ->helperText('JPG, PNG o WEBP, máx. 4 MB'),
                                ]),

                            Section::make('Contacto')
                                ->description('Medios de comunicación con el cliente')
                                ->icon('heroicon-m-phone')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('telefono_normal')
                                        ->label('Teléfono normal')
                                        ->placeholder('+(503) 1234-5678')
                                        ->tel()
                                        ->maxLength(30),

                                    Forms\Components\TextInput::make('telefono_whatsapp')
                                        ->label('Teléfono WhatsApp')
                                        ->placeholder('+(503) 1234-5678')
                                        ->tel()
                                        ->maxLength(30),

                                    Forms\Components\TextInput::make('email')
                                        ->label('Correo electrónico')
                                        ->placeholder('cliente@email.com')
                                        ->email()
                                        ->unique(Cliente::class, 'email', ignoreRecord: true)
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Ubicación')
                                ->description('Información de ubicación del cliente (El Salvador)')
                                ->icon('heroicon-m-map-pin')
                                ->columns(3)
                                ->components([
                                    Forms\Components\TextInput::make('departamento')
                                        ->label('Departamento')
                                        ->placeholder('Ej: San Salvador')
                                        ->maxLength(100),

                                    Forms\Components\TextInput::make('municipio')
                                        ->label('Municipio')
                                        ->placeholder('Ej: San Salvador')
                                        ->maxLength(100),

                                    Forms\Components\TextInput::make('distrito')
                                        ->label('Distrito')
                                        ->placeholder('Ej: Distrito 1')
                                        ->maxLength(100),

                                    Forms\Components\TextInput::make('direccion')
                                        ->label('Dirección completa')
                                        ->placeholder('Calle, número, colonia')
                                        ->maxLength(500)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('latitud')
                                        ->label('Latitud')
                                        ->placeholder('Ej: 13.693395')
                                        ->regex('/^-?\d+(\.\d{1,8})?$/')
                                        ->helperText('Formato: -90 a 90'),

                                    Forms\Components\TextInput::make('longitud')
                                        ->label('Longitud')
                                        ->placeholder('Ej: -89.219541')
                                        ->regex('/^-?\d+(\.\d{1,8})?$/')
                                        ->helperText('Formato: -180 a 180'),
                                ]),
                        ]),

                    // ── Tab 2: Otros datos ────────────────────────────────────
                    Tabs\Tab::make('Otros datos')
                        ->icon('heroicon-m-document-text')
                        ->components([
                            Section::make('Asignación de ruta de cobro')
                                ->description('Selecciona la ruta de cobro del cliente')
                                ->icon('heroicon-m-map')
                                ->columns(1)
                                ->components([
                                    Forms\Components\Select::make('ruta_cobro_id')
                                        ->label('Ruta de cobro')
                                        ->placeholder('Selecciona una ruta...')
                                        ->relationship('rutaCobro', 'nombre', fn ($query) => $query->where('activa', true)->orderBy('nombre'))
                                        ->searchable()
                                        ->preload(),
                                ]),

                            Section::make('Referencia familiar 1')
                                ->description('Primera referencia de familiar directo')
                                ->icon('heroicon-m-heart')
                                ->columns(3)
                                ->components([
                                    Forms\Components\TextInput::make('ref_fam1_nombre')
                                        ->label('Nombre completo')
                                        ->placeholder('Ej: Carlos González')
                                        ->maxLength(150),

                                    Forms\Components\TextInput::make('ref_fam1_telefono')
                                        ->label('Teléfono')
                                        ->placeholder('+(503) 0000-0000')
                                        ->tel()
                                        ->maxLength(30),

                                    Forms\Components\TextInput::make('ref_fam1_parentesco')
                                        ->label('Parentesco')
                                        ->placeholder('Ej: Hermano, Padre')
                                        ->maxLength(60),
                                ]),

                            Section::make('Referencia familiar 2')
                                ->description('Segunda referencia de familiar directo')
                                ->icon('heroicon-m-heart')
                                ->columns(3)
                                ->components([
                                    Forms\Components\TextInput::make('ref_fam2_nombre')
                                        ->label('Nombre completo')
                                        ->placeholder('Ej: Ana Martínez')
                                        ->maxLength(150),

                                    Forms\Components\TextInput::make('ref_fam2_telefono')
                                        ->label('Teléfono')
                                        ->placeholder('+(503) 0000-0000')
                                        ->tel()
                                        ->maxLength(30),

                                    Forms\Components\TextInput::make('ref_fam2_parentesco')
                                        ->label('Parentesco')
                                        ->placeholder('Ej: Tío, Prima')
                                        ->maxLength(60),
                                ]),

                            Section::make('Referencia conocida 1')
                                ->description('Primera referencia de persona conocida')
                                ->icon('heroicon-m-user-plus')
                                ->columns(3)
                                ->components([
                                    Forms\Components\TextInput::make('ref_con1_nombre')
                                        ->label('Nombre completo')
                                        ->placeholder('Ej: Luis Ramírez')
                                        ->maxLength(150),

                                    Forms\Components\TextInput::make('ref_con1_telefono')
                                        ->label('Teléfono')
                                        ->placeholder('+(503) 0000-0000')
                                        ->tel()
                                        ->maxLength(30),

                                    Forms\Components\TextInput::make('ref_con1_trabajo')
                                        ->label('Lugar de trabajo')
                                        ->placeholder('Ej: Empresa XYZ')
                                        ->maxLength(150),
                                ]),

                            Section::make('Referencia conocida 2')
                                ->description('Segunda referencia de persona conocida')
                                ->icon('heroicon-m-user-plus')
                                ->columns(3)
                                ->components([
                                    Forms\Components\TextInput::make('ref_con2_nombre')
                                        ->label('Nombre completo')
                                        ->placeholder('Ej: Sofía López')
                                        ->maxLength(150),

                                    Forms\Components\TextInput::make('ref_con2_telefono')
                                        ->label('Teléfono')
                                        ->placeholder('+(503) 0000-0000')
                                        ->tel()
                                        ->maxLength(30),

                                    Forms\Components\TextInput::make('ref_con2_trabajo')
                                        ->label('Lugar de trabajo')
                                        ->placeholder('Ej: Comercios ABC')
                                        ->maxLength(150),
                                ]),
                        ]),

                    // ── Tab 3: Crédito y estado ───────────────────────────────
                    Tabs\Tab::make('Crédito y estado')
                        ->icon('heroicon-m-banknotes')
                        ->components([
                            Section::make('Crédito')
                                ->description('Configuración del crédito del cliente')
                                ->icon('heroicon-m-credit-card')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('limite_credito')
                                        ->label('Límite de crédito')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->minValue(0)
                                        ->helperText('Monto máximo de crédito autorizado'),

                                    Forms\Components\TextInput::make('saldo')
                                        ->label('Saldo actual')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->helperText('Saldo pendiente del cliente'),
                                ]),

                            Section::make('Estado')
                                ->description('Controla si el cliente está activo en el sistema')
                                ->icon('heroicon-m-check-circle')
                                ->components([
                                    Forms\Components\Toggle::make('activo')
                                        ->label('Cliente activo')
                                        ->default(true)
                                        ->helperText('Los clientes inactivos no aparecen en las ventas.'),
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
                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Cliente')
                    ->searchable(['nombre', 'apellido'])
                    ->sortable(['nombre'])
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('dui')
                    ->label('DUI')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable(),

                Tables\Columns\TextColumn::make('telefono_normal')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->icon('heroicon-m-envelope')
                    ->searchable(),

                Tables\Columns\TextColumn::make('limite_credito')
                    ->label('Límite crédito')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('USD')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
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

    // ── Relation Managers ─────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            CuentasCobrarRelationManager::class,
            VentasRelationManager::class,
            PagosVentaRelationManager::class,
        ];
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'view'   => Pages\ViewCliente::route('/{record}'),
            'edit'   => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
