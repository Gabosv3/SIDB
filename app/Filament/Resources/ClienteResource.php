<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers\CuentasCobrarRelationManager;
use App\Filament\Resources\ClienteResource\RelationManagers\VentasRelationManager;
use App\Filament\Resources\ClienteResource\RelationManagers\PagosVentaRelationManager;
use App\Forms\Components\MapPicker;
use App\Data\ElSalvadorData;
use App\Models\Cliente;
use App\Models\Cobrador;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions;
use Filament\Actions\Action;
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
        return 'Ventas';
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
                                        ->maxLength(100)
                                        ->validationMessages([
                                            'required' => 'El nombre es obligatorio.',
                                            'max'      => 'El nombre no puede superar los 100 caracteres.',
                                        ]),

                                    Forms\Components\TextInput::make('apellido')
                                        ->label('Apellido(s)')
                                        ->placeholder('Ej: González Pérez')
                                        ->required()
                                        ->maxLength(100)
                                        ->validationMessages([
                                            'required' => 'El apellido es obligatorio.',
                                            'max'      => 'El apellido no puede superar los 100 caracteres.',
                                        ]),

                                    Forms\Components\TextInput::make('nombre_conyuge')
                                        ->label('Nombre del cónyuge')
                                        ->placeholder('Ej: María Pérez')
                                        ->maxLength(200)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('dui')
                                        ->label('DUI')
                                        ->placeholder('00000000-0')
                                        ->mask('99999999-9')
                                        ->required()
                                        ->unique(Cliente::class, 'dui', ignoreRecord: true)
                                        ->regex('/^\d{8}-\d$/')
                                        ->maxLength(10)
                                        ->helperText('Formato: 00000000-0')
                                        ->validationMessages([
                                            'required' => 'El DUI es obligatorio.',
                                            'unique'   => 'Este DUI ya está registrado.',
                                            'regex'    => 'El DUI debe tener el formato 00000000-0.',
                                            'max'      => 'El DUI no puede superar los 10 caracteres.',
                                        ]),

                                    Forms\Components\TextInput::make('nit')
                                        ->label('NIT')
                                        ->placeholder('0000-000000-000-0')
                                        ->unique(Cliente::class, 'nit', ignoreRecord: true)
                                        ->maxLength(20)
                                        ->helperText('Número de Identificación Tributaria')
                                        ->validationMessages([
                                            'unique' => 'Este NIT ya está registrado.',
                                            'max'    => 'El NIT no puede superar los 20 caracteres.',
                                        ]),
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
                                        ->disk('public')
                                        ->directory('dui/frente')
                                        ->visibility('public')
                                        ->required()
                                        ->helperText('Requerido · JPG, PNG o WEBP, máx. 4 MB')
                                        ->validationMessages([
                                            'required' => 'La foto del frente del DUI es obligatoria.',
                                            'mimes'    => 'Solo se permiten imágenes JPG, PNG o WEBP.',
                                            'max'      => 'La imagen no puede superar los 4 MB.',
                                        ]),

                                    Forms\Components\FileUpload::make('dui_foto_reverso')
                                        ->label('DUI — Reverso')
                                        ->image()
                                        ->imageEditor()
                                        ->imagePreviewHeight('180')
                                        ->uploadingMessage('Subiendo...')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->maxSize(4096)
                                        ->disk('public')
                                        ->directory('dui/reverso')
                                        ->visibility('public')
                                        ->required()
                                        ->helperText('Requerido · JPG, PNG o WEBP, máx. 4 MB')
                                        ->validationMessages([
                                            'required' => 'La foto del reverso del DUI es obligatoria.',
                                            'mimes'    => 'Solo se permiten imágenes JPG, PNG o WEBP.',
                                            'max'      => 'La imagen no puede superar los 4 MB.',
                                        ]),
                                ]),

                            Section::make('Fotografía de la Casa')
                                ->description('Foto de la vivienda del cliente')
                                ->icon('heroicon-m-home')
                                ->columns(1)
                                ->components([
                                    Forms\Components\FileUpload::make('foto_casa')
                                        ->label('Fotografía de la Casa')
                                        ->image()
                                        ->imageEditor()
                                        ->imagePreviewHeight('200')
                                        ->uploadingMessage('Subiendo...')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->maxSize(4096)
                                        ->disk('public')
                                        ->directory('casas')
                                        ->visibility('public')
                                        ->helperText('Opcional · JPG, PNG o WEBP, máx. 4 MB'),
                                ]),

                            Section::make('Contacto')
                                ->description('Medios de comunicación con el cliente')
                                ->icon('heroicon-m-phone')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('telefono_normal')
                                        ->label('Teléfono normal')
                                        ->placeholder('7654-3210')
                                        ->mask('9999-9999')
                                        ->tel()
                                        ->required()
                                        ->regex('/^\d{4}-\d{4}$/')
                                        ->maxLength(9)
                                        ->helperText('Formato: 0000-0000')
                                        ->validationMessages([
                                            'required' => 'El teléfono es obligatorio.',
                                            'regex'    => 'El teléfono debe tener el formato 0000-0000.',
                                            'max'      => 'El teléfono no puede superar los 9 caracteres.',
                                        ]),

                                    Forms\Components\TextInput::make('telefono_whatsapp')
                                        ->label('Teléfono WhatsApp')
                                        ->placeholder('7654-3210')
                                        ->mask('9999-9999')
                                        ->tel()
                                        ->required()
                                        ->regex('/^\d{4}-\d{4}$/')
                                        ->maxLength(9)
                                        ->helperText('Formato: 0000-0000')
                                        ->validationMessages([
                                            'required' => 'El teléfono de WhatsApp es obligatorio.',
                                            'regex'    => 'El teléfono debe tener el formato 0000-0000.',
                                            'max'      => 'El teléfono no puede superar los 9 caracteres.',
                                        ]),

                                    Forms\Components\TextInput::make('email')
                                        ->label('Correo electrónico')
                                        ->placeholder('cliente@email.com')
                                        ->email()
                                        ->unique(Cliente::class, 'email', ignoreRecord: true)
                                        ->maxLength(255)
                                        ->columnSpanFull()
                                        ->validationMessages([
                                            'email'  => 'El correo electrónico no tiene un formato válido.',
                                            'unique' => 'Este correo ya está registrado.',
                                            'max'    => 'El correo no puede superar los 255 caracteres.',
                                        ]),
                                ]),

                            Section::make('Ubicación')
                                ->description('Información de ubicación del cliente (El Salvador)')
                                ->icon('heroicon-m-map-pin')
                                ->columns(3)
                                ->components([
                                    Forms\Components\Select::make('departamento')
                                        ->label('Departamento')
                                        ->placeholder('Selecciona un departamento')
                                        ->options(ElSalvadorData::departamentos())
                                        ->searchable()
                                        ->required()
                                        ->live()
                                        ->validationMessages([
                                            'required' => 'El departamento es obligatorio.',
                                        ]),

                                    Forms\Components\Select::make('municipio')
                                        ->label('Municipio')
                                        ->placeholder('Selecciona un municipio')
                                        ->options(fn ($get) => ElSalvadorData::municipios($get('departamento') ?? ''))
                                        ->searchable()
                                        ->required()
                                        ->live()
                                        ->validationMessages([
                                            'required' => 'El municipio es obligatorio.',
                                        ]),

                                    Forms\Components\Select::make('distrito')
                                        ->label('Distrito')
                                        ->placeholder('Selecciona un distrito')
                                        ->options(fn ($get) => ElSalvadorData::distritos($get('municipio') ?? ''))
                                        ->searchable()
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'El distrito es obligatorio.',
                                        ]),

                                    Forms\Components\TextInput::make('direccion')
                                        ->label('Dirección completa')
                                        ->placeholder('Calle, número, colonia')
                                        ->required()
                                        ->maxLength(500)
                                        ->columnSpanFull()
                                        ->validationMessages([
                                            'required' => 'La dirección es obligatoria.',
                                            'max'      => 'La dirección no puede superar los 500 caracteres.',
                                        ]),

                                    Forms\Components\TextInput::make('pegar_ubicacion')
                                        ->label('Pegar ubicación de WhatsApp')
                                        ->placeholder('Pega aquí el link o las coordenadas tal como llegaron')
                                        ->helperText('Detecta las coordenadas automáticamente y llena latitud/longitud — no se guarda como campo aparte.')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (! $state) {
                                                return;
                                            }
                                            if (preg_match('/(-?\d{1,3}\.\d{3,})[,\s]+(-?\d{1,3}\.\d{3,})/', $state, $m)) {
                                                $lat = (float) $m[1];
                                                $lng = (float) $m[2];
                                                if (abs($lat) <= 90 && abs($lng) <= 180) {
                                                    $set('latitud', number_format($lat, 6, '.', ''));
                                                    $set('longitud', number_format($lng, 6, '.', ''));
                                                }
                                            }
                                        })
                                        ->columnSpanFull(),

                                    MapPicker::make()
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('latitud')
                                        ->label('Latitud')
                                        ->placeholder('Ej: 13.693395')
                                        ->required()
                                        ->regex('/^-?\d+(\.\d{1,8})?$/')
                                        ->rules(['required', 'numeric', 'between:-90,90'])
                                        ->helperText('Rango: -90 a 90')
                                        ->validationMessages([
                                            'required' => 'La latitud es obligatoria.',
                                            'regex'    => 'Formato inválido (ej: 13.693395).',
                                            'numeric'  => 'La latitud debe ser un número.',
                                            'between'  => 'La latitud debe estar entre -90 y 90.',
                                        ]),

                                    Forms\Components\TextInput::make('longitud')
                                        ->label('Longitud')
                                        ->placeholder('Ej: -89.219541')
                                        ->required()
                                        ->regex('/^-?\d+(\.\d{1,8})?$/')
                                        ->rules(['required', 'numeric', 'between:-180,180'])
                                        ->helperText('Rango: -180 a 180')
                                        ->validationMessages([
                                            'required' => 'La longitud es obligatoria.',
                                            'regex'    => 'Formato inválido (ej: -89.219541).',
                                            'numeric'  => 'La longitud debe ser un número.',
                                            'between'  => 'La longitud debe estar entre -180 y 180.',
                                        ]),
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

                Tables\Columns\TextColumn::make('codigo_anterior')
                    ->label('Cód. Anterior')
                    ->searchable()
                    ->placeholder('—')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                // Botón WhatsApp — enlace directo a wa.me con el número del cliente.
                // Provisional: sin integración propia (Baileys se descontinuó), en
                // espera de reconstruir con WhatsApp Coexistence.
                Action::make('whatsapp')
                    ->label('💬 WhatsApp')
                    ->icon('heroicon-m-chat-bubble-left')
                    ->color('success')
                    ->visible(fn (Cliente $record) => filled($record->telefono_whatsapp))
                    ->url(function (Cliente $record) {
                        $numero = preg_replace('/\D/', '', $record->telefono_whatsapp);
                        if (strlen($numero) === 8) {
                            $numero = '503'.$numero;
                        }

                        return "https://wa.me/{$numero}";
                    })
                    ->openUrlInNewTab(),
                // Acciones estándar
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
            'index'    => Pages\ListClientes::route('/'),
            'create'   => Pages\CreateCliente::route('/create'),
            'view'     => Pages\ViewCliente::route('/{record}'),
            'edit'     => Pages\EditCliente::route('/{record}/edit'),
            'importar' => Pages\ImportarClientes::route('/importar'),
        ];
    }
}
