<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    // Usuarios son globales: gestionados por super_admin independiente de sucursal
    protected static bool $isScopedToTenant = false;

    // ── Shield permissions ────────────────────────────────────────────────────

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    // ── Navigation (métodos para evitar incompatibilidades de tipo en PHP 8.4) ─

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return 'Usuarios';
    }

    public static function getModelLabel(): string
    {
        return 'Usuario';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Usuarios';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Administración';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Administración de usuario')
                ->tabs([
                    Tabs\Tab::make('Información')
                        ->icon('heroicon-m-user-circle')
                        ->components([
                            Section::make('Datos personales')
                                ->description('Información básica del usuario')
                                ->icon('heroicon-m-user')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nombre completo')
                                        ->placeholder('Ej: Juan González')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('email')
                                        ->label('Correo electrónico')
                                        ->placeholder('usuario@example.com')
                                        ->email()
                                        ->required()
                                        ->unique(User::class, 'email', ignoreRecord: true)
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Seguridad')
                                ->description('Actualiza la contraseña del usuario')
                                ->icon('heroicon-m-lock-closed')
                                ->columns(2)
                                ->components([
                                    Forms\Components\TextInput::make('password')
                                        ->label('Contraseña nueva')
                                        ->placeholder('Dejar vacío para no cambiar')
                                        ->password()
                                        ->revealable()
                                        ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                        ->dehydrated(fn (?string $state): bool => filled($state))
                                        ->required(fn (string $operation): bool => $operation === 'create')
                                        ->minLength(8)
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('password_confirmation')
                                        ->label('Confirmar contraseña')
                                        ->placeholder('Repite la contraseña')
                                        ->password()
                                        ->revealable()
                                        ->same('password')
                                        ->dehydrated(false)
                                        ->requiredWith('password'),
                                ]),

                            Section::make('Estado de la cuenta')
                                ->description('Controla si este usuario puede iniciar sesión')
                                ->icon('heroicon-m-power')
                                ->columns(1)
                                ->components([
                                    Forms\Components\Select::make('account_status')
                                        ->label('Estado')
                                        ->options([
                                            'activa' => 'Activa',
                                            'bloqueada' => 'Bloqueada',
                                            'desactivada' => 'Desactivada',
                                        ])
                                        ->default('activa')
                                        ->required()
                                        ->native(false)
                                        ->disabled(fn (?User $record): bool => $record !== null && $record->id === auth()->id())
                                        ->helperText(fn (?User $record): string => $record !== null && $record->id === auth()->id()
                                            ? 'No puedes cambiar el estado de tu propia cuenta.'
                                            : 'Bloqueada o Desactivada impiden iniciar sesión y usar la API del POS; se cierran todas las sesiones activas de ese usuario al guardar.'),
                                ]),
                        ]),

                    Tabs\Tab::make('Permisos')
                        ->icon('heroicon-m-shield-check')
                        ->components([
                            Section::make('Roles del sistema')
                                ->description('Asigna los roles que tendrá este usuario')
                                ->icon('heroicon-m-cog-6-tooth')
                                ->columns(1)
                                ->components([
                                    Forms\Components\Select::make('roles')
                                        ->label('Roles')
                                        ->relationship('roles', 'name')
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->columnSpanFull()
                                        ->helperText('Un usuario puede tener múltiples roles. Los permisos se heredan de los roles asignados.'),
                                ]),

                            Section::make('Asignación de sucursales')
                                ->description('Especifica a cuáles sucursales tiene acceso')
                                ->icon('heroicon-m-building-storefront')
                                ->columns(1)
                                ->components([
                                    Forms\Components\Select::make('sucursales')
                                        ->label('Sucursales')
                                        ->relationship('sucursales', 'nombre', fn (Builder $query) => $query->where('activo', true))
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->columnSpanFull()
                                        ->helperText('Este usuario solo podrá ver datos de las sucursales seleccionadas.'),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('sucursales.nombre')
                    ->label('Sucursales')
                    ->badge()
                    ->color('info')
                    ->separator(','),

                Tables\Columns\TextColumn::make('account_status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'activa' => 'Activa',
                        'bloqueada' => 'Bloqueada',
                        'desactivada' => 'Desactivada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'activa' => 'success',
                        'bloqueada' => 'warning',
                        'desactivada' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('account_status')
                    ->label('Estado')
                    ->options([
                        'activa' => 'Activa',
                        'bloqueada' => 'Bloqueada',
                        'desactivada' => 'Desactivada',
                    ]),
            ])
            ->actions([
                Actions\Action::make('verPerfil')
                    ->label('Ver perfil')
                    ->icon('heroicon-m-identification')
                    ->color('gray')
                    ->url(fn (User $record) => route('empleados.show', [\Filament\Facades\Filament::getTenant()?->id ?? 1, $record->id]))
                    ->openUrlInNewTab(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
