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

                                    Forms\Components\TextInput::make('alias')
                                        ->label('Alias (opcional)')
                                        ->placeholder('Ej: Juanito')
                                        ->helperText('Si lo llenas, la app le muestra este nombre en vez del nombre completo — en tickets, saludo, menú, etc. El nombre completo de arriba no cambia, solo lo que ve en la app.')
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
                Tables\Filters\TrashedFilter::make(),

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
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Actions\Action::make('verPerfil')
                    ->label('Ver perfil')
                    ->icon('heroicon-m-identification')
                    ->color('gray')
                    ->url(fn (User $record) => route('empleados.show', [\Filament\Facades\Filament::getTenant()?->id ?? 1, $record->id]))
                    ->openUrlInNewTab(),
                Actions\EditAction::make(),
                // El soft delete ya no dispara el cascade hacia ficha/pagos/
                // documentos, pero se deja el bloqueo igual: ocultar un
                // usuario con ficha de empleado rompería esa relación
                // ($perfil->user quedaría null) en el expediente. Para
                // bloquear el acceso de un empleado sin tocar su ficha, ya
                // existe "Bloquear acceso" en su perfil.
                Actions\DeleteAction::make()
                    ->before(function (User $record, Actions\DeleteAction $action) {
                        if (static::tieneDatosBloqueantes($record->id)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar')
                                ->body('Este usuario ya tiene ficha de empleado, pagos o documentos registrados -- ocultarlo rompería su expediente. Usa "Bloquear acceso" en su perfil en vez de eliminarlo.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
                Actions\RestoreAction::make(),
                Actions\ForceDeleteAction::make()
                    ->before(function (User $record, Actions\ForceDeleteAction $action) {
                        if (static::tieneDatosBloqueantes($record->id)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar para siempre')
                                ->body('Este usuario ya tiene ficha de empleado, pagos o documentos registrados -- borrarlo definitivamente se los llevaría en cascada.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->before(function (\Illuminate\Support\Collection $records, Actions\DeleteBulkAction $action) {
                            $conDatos = $records->filter(fn (User $u) => static::tieneDatosBloqueantes($u->id));

                            if ($conDatos->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No se puede eliminar')
                                    ->body('Algunos usuarios seleccionados ya tienen historial de empleado: '.$conDatos->pluck('name')->join(', ').'.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                    Actions\RestoreBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make()
                        ->before(function (\Illuminate\Support\Collection $records, Actions\ForceDeleteBulkAction $action) {
                            $conDatos = $records->filter(fn (User $u) => static::tieneDatosBloqueantes($u->id));

                            if ($conDatos->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No se puede eliminar para siempre')
                                    ->body('Algunos usuarios seleccionados ya tienen historial de empleado: '.$conDatos->pluck('name')->join(', ').'.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }

    /**
     * employee_profiles/employee_documents/employee_pagos cascadean por
     * user_id -- borrar el usuario se llevaría en cascada toda su ficha de
     * RRHH y su historial de pagos, sin ningún aviso.
     */
    public static function tieneDatosBloqueantes(int $userId): bool
    {
        return \App\Models\EmployeeProfile::where('user_id', $userId)->exists()
            || \App\Models\EmployeePago::where('user_id', $userId)->exists()
            || \App\Models\EmployeeDocument::where('user_id', $userId)->exists();
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
