<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendedorResource\Pages;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Vendedor;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VendedorResource extends Resource
{
    protected static ?string $model = Vendedor::class;

    // Centralizado: la gestión de vendedores ahora se hace desde el perfil
    // del empleado (Usuarios → Ver perfil → pestaña Laboral), no desde este
    // módulo aparte. Se mantiene registrado (rutas, permisos, relaciones)
    // pero fuera del menú.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-briefcase';
    }

    public static function getNavigationLabel(): string
    {
        return 'Vendedores';
    }

    public static function getModelLabel(): string
    {
        return 'Vendedor';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Vendedores';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Ventas';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cuenta de Usuario')
                ->icon('heroicon-m-user-circle')
                ->description('Vincula este vendedor a una cuenta de acceso al sistema y al POS.')
                ->columns(2)
                ->components([
                    Forms\Components\Select::make('user_id')
                        ->label('Usuario del sistema')
                        ->placeholder('Seleccionar usuario...')
                        ->relationship(
                            name: 'user',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query, $record) => $query
                                ->whereDoesntHave('vendedor', fn ($q) => $q->when($record, fn ($q) => $q->where('id', '!=', $record?->id))),
                        )
                        ->getOptionLabelFromRecordUsing(fn (User $u) => "{$u->name} ({$u->email})".($u->cobrador ? ' — también es cobrador' : ''))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Un usuario puede tener perfil de vendedor y de cobrador a la vez. Requerido para acceder al POS.'),

                    Forms\Components\Toggle::make('es_cobrador')
                        ->label('También es cobrador')
                        ->default(false)
                        ->inline(false)
                        ->helperText('Este vendedor podrá gestionar cobros además de ventas.'),
                ]),

            Section::make('Datos Personales')
                ->icon('heroicon-m-user')
                ->columns(2)
                ->components([
                    Forms\Components\TextInput::make('codigo')
                        ->label('Código')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('Auto-generado'),

                    Forms\Components\Toggle::make('activo')
                        ->label('Activo')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('apellido')
                        ->label('Apellido')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->relationship('sucursal', 'nombre')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->formatStateUsing(fn ($record) => "{$record->nombre} {$record->apellido}")
                    ->searchable(['nombre', 'apellido'])
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->icon('heroicon-m-user-circle')
                    ->placeholder('Sin usuario')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('es_cobrador')
                    ->label('Cobra')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-badge')
                    ->falseIcon('heroicon-m-minus-circle')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ventas_count')
                    ->label('Ventas')
                    ->counts('ventas')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sucursal_id')
                    ->label('Sucursal')
                    ->relationship('sucursal', 'nombre')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado'),
            ])
            ->actions([
                Actions\Action::make('verPerfil')
                    ->label('Ver perfil')
                    ->icon('heroicon-m-identification')
                    ->color('gray')
                    ->visible(fn (Vendedor $record) => $record->user_id !== null)
                    ->url(fn (Vendedor $record) => route('empleados.show', [\Filament\Facades\Filament::getTenant()?->id ?? 1, $record->user_id]))
                    ->openUrlInNewTab(),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVendedores::route('/'),
            'create' => Pages\CreateVendedor::route('/create'),
            'edit'   => Pages\EditVendedor::route('/{record}/edit'),
        ];
    }
}
