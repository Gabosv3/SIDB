<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CobradorResource\Pages;
use App\Filament\Resources\CobradorResource\RelationManagers\RutasCobroRelationManager;
use App\Models\Cobrador;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CobradorResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Cobrador::class;

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
        return 'Cobradores';
    }

    public static function getModelLabel(): string
    {
        return 'Cobrador';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Cobradores';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cuenta de Usuario')
                ->description('Vincula este cobrador a una cuenta de acceso al sistema y al POS.')
                ->icon('heroicon-m-user-circle')
                ->components([
                    Forms\Components\Select::make('user_id')
                        ->label('Usuario del sistema')
                        ->placeholder('Seleccionar usuario...')
                        ->relationship(
                            name: 'user',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query, $record) => $query
                                ->whereDoesntHave('cobrador', fn ($q) => $q->when($record, fn ($q) => $q->where('id', '!=', $record?->id))),
                        )
                        ->getOptionLabelFromRecordUsing(fn (User $u) => "{$u->name} ({$u->email})".($u->vendedor ? ' — también es vendedor' : ''))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Un usuario puede tener perfil de vendedor y de cobrador a la vez. Requerido para acceder al POS.'),
                ]),

            Section::make('Sucursal')
                ->description('La sucursal a la que pertenece este cobrador')
                ->icon('heroicon-m-building-storefront')
                ->hidden(fn (string $operation) => $operation === 'create')
                ->components([
                    Forms\Components\Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->relationship('sucursal', 'nombre')
                        ->disabled()
                        ->dehydrated(),
                ]),

            Section::make('Datos del cobrador')
                ->description('Información personal del cobrador')
                ->icon('heroicon-m-user-circle')
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

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->placeholder('+(503) 1234-5678')
                        ->tel()
                        ->maxLength(30),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->placeholder('cobrador@email.com')
                        ->email()
                        ->unique(Cobrador::class, 'email', ignoreRecord: true)
                        ->maxLength(255),
                ]),

            Section::make('Estado')
                ->description('Controla si el cobrador está activo en el sistema')
                ->icon('heroicon-m-check-circle')
                ->components([
                    Forms\Components\Toggle::make('activo')
                        ->label('Cobrador activo')
                        ->default(true)
                        ->helperText('Los cobradores inactivos no aparecen en la asignación de clientes.'),
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
                    ->label('Cobrador')
                    ->searchable(['nombre', 'apellido'])
                    ->sortable(['nombre'])
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->icon('heroicon-m-phone')
                    ->copyable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->icon('heroicon-m-envelope')
                    ->searchable()
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
                Actions\Action::make('verPerfil')
                    ->label('Ver perfil')
                    ->icon('heroicon-m-identification')
                    ->color('gray')
                    ->visible(fn (Cobrador $record) => $record->user_id !== null)
                    ->url(fn (Cobrador $record) => route('empleados.show', [\Filament\Facades\Filament::getTenant()?->id ?? 1, $record->user_id]))
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

    // ── Relation Managers ─────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            RutasCobroRelationManager::class,
        ];
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCobradores::route('/'),
            'create' => Pages\CreateCobrador::route('/create'),
            'edit'   => Pages\EditCobrador::route('/{record}/edit'),
        ];
    }
}
