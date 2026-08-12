<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupervisorResource\Pages;
use App\Models\RutaCobro;
use App\Models\Supervisor;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SupervisorResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Supervisor::class;

    // Centralizado: la gestión de supervisores ahora se hace desde el perfil
    // del empleado (Usuarios → Ver perfil → pestaña Laboral), no desde este
    // módulo aparte. Se mantiene registrado (rutas, permisos, relaciones)
    // pero fuera del menú.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // ── Shield ────────────────────────────────────────────────────────────────

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-eye';
    }

    public static function getNavigationLabel(): string
    {
        return 'Supervisores';
    }

    public static function getModelLabel(): string
    {
        return 'Supervisor';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Supervisores';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cuenta de Usuario')
                ->description('Vincula este supervisor a una cuenta de acceso al sistema y al POS.')
                ->icon('heroicon-m-user-circle')
                ->components([
                    Forms\Components\Select::make('user_id')
                        ->label('Usuario del sistema')
                        ->placeholder('Seleccionar usuario...')
                        ->relationship(
                            name: 'user',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query, $record) => $query
                                ->whereDoesntHave('supervisor', fn ($q) => $q->when($record, fn ($q) => $q->where('id', '!=', $record?->id))),
                        )
                        ->getOptionLabelFromRecordUsing(fn (User $u) => "{$u->name} ({$u->email})")
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Un mismo usuario puede tener perfil de cobrador y de supervisor a la vez.'),
                ]),

            Section::make('Datos del supervisor')
                ->icon('heroicon-m-identification')
                ->columns(2)
                ->components([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre(s)')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('apellido')
                        ->label('Apellido(s)')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(30),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->unique(Supervisor::class, 'email', ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->relationship('sucursal', 'nombre')
                        ->searchable()
                        ->preload(),

                    Forms\Components\Toggle::make('activo')
                        ->label('Supervisor activo')
                        ->default(true)
                        ->helperText('Un supervisor inactivo pierde acceso al POS.'),
                ]),

            Section::make('Rutas a supervisar')
                ->description('El supervisor puede cobrar y consultar el historial de estas rutas, además de las suyas propias si también es cobrador.')
                ->icon('heroicon-m-map')
                ->components([
                    Forms\Components\Select::make('rutasSupervisadas')
                        ->label('Rutas asignadas')
                        ->relationship('rutasSupervisadas', 'nombre')
                        ->getOptionLabelFromRecordUsing(fn (RutaCobro $r) => $r->nombre_con_dia . ($r->cobrador ? ' — cobrador: ' . $r->cobrador->nombre_completo : ''))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('El supervisor ve y cobra los mismos clientes que el cobrador titular de cada ruta seleccionada.'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Supervisor')
                    ->searchable(['nombre', 'apellido'])
                    ->sortable(['nombre'])
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rutasSupervisadas_count')
                    ->label('Rutas')
                    ->counts('rutasSupervisadas')
                    ->badge(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->icon('heroicon-m-phone')
                    ->copyable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->icon('heroicon-m-user-circle')
                    ->placeholder('Sin usuario')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
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
            'index'  => Pages\ListSupervisores::route('/'),
            'create' => Pages\CreateSupervisor::route('/create'),
            'edit'   => Pages\EditSupervisor::route('/{record}/edit'),
        ];
    }
}
