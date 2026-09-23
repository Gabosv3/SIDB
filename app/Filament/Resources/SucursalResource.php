<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SucursalResource\Pages;
use App\Models\Sucursal;
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

class SucursalResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Sucursal::class;

    // Sucursal ES el modelo tenant: no se filtra por sí mismo
    protected static bool $isScopedToTenant = false;

    // ── Shield permissions ────────────────────────────────────────────────────

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    // ── Navigation (métodos para evitar incompatibilidades de tipo en PHP 8.4) ─

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-building-storefront';
    }

    public static function getNavigationLabel(): string
    {
        return 'Sucursales';
    }

    public static function getModelLabel(): string
    {
        return 'Sucursal';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Sucursales';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Administración';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Gestión de sucursal')
                ->tabs([
                    Tabs\Tab::make('Información general')
                        ->icon('heroicon-m-information-circle')
                        ->components([
                            Section::make('Datos básicos')
                                ->description('Información principal de la sucursal')
                                ->icon('heroicon-m-building-storefront')
                                ->columns(1)
                                ->components([
                                    Forms\Components\TextInput::make('nombre')
                                        ->label('Nombre de la sucursal')
                                        ->placeholder('Ej: Sucursal Central')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('codigo')
                                        ->label('Código único')
                                        ->placeholder('Ej: SC-001')
                                        ->unique(Sucursal::class, 'codigo', ignoreRecord: true)
                                        ->maxLength(50)
                                        ->helperText('Código interno único para identificar la sucursal'),
                                ]),

                            Section::make('Ubicación')
                                ->description('Detalles de ubicación y contacto')
                                ->icon('heroicon-m-map-pin')
                                ->columns(1)
                                ->components([
                                    Forms\Components\TextInput::make('direccion')
                                        ->label('Dirección completa')
                                        ->placeholder('Calle, número, ciudad')
                                        ->maxLength(500)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('telefono')
                                        ->label('Teléfono')
                                        ->placeholder('+(503) 1234-5678')
                                        ->tel()
                                        ->maxLength(30),

                                    Forms\Components\TextInput::make('email')
                                        ->label('Correo electrónico')
                                        ->placeholder('contacto@sucursal.com')
                                        ->email()
                                        ->maxLength(255),
                                ]),
                        ]),

                    Tabs\Tab::make('Estado')
                        ->icon('heroicon-m-power')
                        ->components([
                            Section::make('Disponibilidad')
                                ->description('Controla si esta sucursal está operativa')
                                ->icon('heroicon-m-check-circle')
                                ->columns(1)
                                ->components([
                                    Forms\Components\Toggle::make('activo')
                                        ->label('Sucursal activa')
                                        ->helperText('Cuando está activada, los usuarios pueden acceder y ver datos de esta sucursal. Desactiva para mantenerla oculta.')
                                        ->default(true)
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
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
            ])
            ->actions([
                Actions\EditAction::make(),
                // El soft delete ya no dispara el cascade hacia cobradores/
                // rutas/clientes/vendedores, pero se deja el bloqueo igual:
                // ocultar una sucursal con datos rompería la relación
                // ($cliente->sucursal, $vendedor->sucursal, etc quedarían
                // null) por todo el sistema, no solo en el selector.
                Actions\DeleteAction::make()
                    ->before(function (Sucursal $record, Actions\DeleteAction $action) {
                        if (static::tieneDatosBloqueantes($record->id)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar')
                                ->body('Esta sucursal ya tiene cobradores, vendedores, rutas de cobro o clientes -- ocultarla rompería esas relaciones por todo el sistema. Desactívala en vez de eliminarla.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
                Actions\RestoreAction::make(),
                Actions\ForceDeleteAction::make()
                    ->before(function (Sucursal $record, Actions\ForceDeleteAction $action) {
                        if (static::tieneDatosBloqueantes($record->id)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar para siempre')
                                ->body('Esta sucursal ya tiene cobradores, vendedores, rutas de cobro o clientes -- borrarla definitivamente se los llevaría en cascada a todos.')
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
                            $conDatos = $records->filter(fn (Sucursal $s) => static::tieneDatosBloqueantes($s->id));

                            if ($conDatos->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No se puede eliminar')
                                    ->body('Algunas sucursales seleccionadas ya tienen datos asociados: '.$conDatos->pluck('nombre')->join(', ').'.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                    Actions\RestoreBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make()
                        ->before(function (\Illuminate\Support\Collection $records, Actions\ForceDeleteBulkAction $action) {
                            $conDatos = $records->filter(fn (Sucursal $s) => static::tieneDatosBloqueantes($s->id));

                            if ($conDatos->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No se puede eliminar para siempre')
                                    ->body('Algunas sucursales seleccionadas ya tienen datos asociados: '.$conDatos->pluck('nombre')->join(', ').'.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }

    /**
     * cobradores/rutas_cobro/clientes/vendedores cascadean por sucursal_id --
     * borrar una sucursal con cualquiera de estos se llevaría en cascada
     * TODO ese árbol (incluyendo, otra vez en cascada, los clientes de sus
     * rutas) sin ningún aviso.
     */
    public static function tieneDatosBloqueantes(int $sucursalId): bool
    {
        return \App\Models\Cobrador::where('sucursal_id', $sucursalId)->exists()
            || \App\Models\Vendedor::where('sucursal_id', $sucursalId)->exists()
            || \App\Models\RutaCobro::where('sucursal_id', $sucursalId)->exists()
            || \App\Models\Cliente::where('sucursal_id', $sucursalId)->exists();
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSucursales::route('/'),
            'create' => Pages\CreateSucursal::route('/create'),
            'edit'   => Pages\EditSucursal::route('/{record}/edit'),
        ];
    }
}
