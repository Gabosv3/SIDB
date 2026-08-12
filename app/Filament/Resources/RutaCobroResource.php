<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RutaCobroResource\Pages;
use App\Filament\Resources\RutaCobroResource\RelationManagers\ClientesRelationManager;
use App\Models\Cobrador;
use App\Models\RutaCobro;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RutaCobroResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = RutaCobro::class;

    // ── Shield ────────────────────────────────────────────────────────────────

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-map';
    }

    public static function getNavigationLabel(): string
    {
        return 'Rutas de cobro';
    }

    public static function getModelLabel(): string
    {
        return 'Ruta de cobro';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Rutas de cobro';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sucursal')
                ->description('La sucursal a la que pertenece esta ruta')
                ->icon('heroicon-m-building-storefront')
                ->hidden(fn (string $operation) => $operation === 'create')
                ->components([
                    Forms\Components\Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->relationship('sucursal', 'nombre')
                        ->disabled()
                        ->dehydrated(),
                ]),

            Section::make('Información de la ruta')
                ->icon('heroicon-m-map')
                ->columns(2)
                ->components([
                    Forms\Components\Select::make('cobrador_id')
                        ->label('Cobrador asignado')
                        ->placeholder('Selecciona un cobrador')
                        ->relationship('cobrador', 'nombre', fn ($query) => $query->where('activo', true)->orderBy('nombre'))
                        ->getOptionLabelFromRecordUsing(fn (Cobrador $record) => "{$record->nombre} {$record->apellido}")
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre de la ruta')
                        ->placeholder('Ej: Ruta Centro')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\Select::make('dia_semana')
                        ->label('Día de cobranza')
                        ->placeholder('Selecciona el día')
                        ->options([
                            'lunes' => 'Lunes',
                            'martes' => 'Martes',
                            'miércoles' => 'Miércoles',
                            'jueves' => 'Jueves',
                            'viernes' => 'Viernes',
                            'sábado' => 'Sábado',
                            'domingo' => 'Domingo',
                        ])
                        ->required(),

                    Forms\Components\Select::make('semana_ciclo')
                        ->label('Semana del ciclo quincenal')
                        ->placeholder('Sin clasificar (se muestra siempre)')
                        ->options([
                            1 => 'Semana 1',
                            2 => 'Semana 2',
                        ])
                        ->helperText('Si se deja sin clasificar, la ruta se muestra en el POS todas las semanas.'),

                    Forms\Components\TextInput::make('descripcion')
                        ->label('Descripción')
                        ->placeholder('Describe los sectores o zonas incluidas en esta ruta')
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('activa')
                        ->label('Ruta activa')
                        ->default(true)
                        ->helperText('Las rutas inactivas no se muestran en la asignación de clientes.'),
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

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('dia_semana')
                    ->label('Día')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : '—')
                    ->color(fn ($state) => match($state) {
                        'lunes' => 'blue',
                        'martes' => 'cyan',
                        'miércoles' => 'purple',
                        'jueves' => 'amber',
                        'viernes' => 'green',
                        'sábado' => 'pink',
                        'domingo' => 'red',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('semana_ciclo')
                    ->label('Semana')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? "Semana {$state}" : 'Todas')
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cobrador.nombre_completo')
                    ->label('Cobrador')
                    ->searchable(['cobrador.nombre', 'cobrador.apellido'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            Cobrador::select('nombre')
                                ->whereColumn('id', 'rutas_cobro.cobrador_id'),
                            $direction
                        );
                    }),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('clientes_count')
                    ->label('Clientes')
                    ->counts('clientes')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('activa')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activa')
                    ->label('Estado')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),

                Tables\Filters\SelectFilter::make('semana_ciclo')
                    ->label('Semana')
                    ->options([
                        1 => 'Semana 1',
                        2 => 'Semana 2',
                    ]),
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

    // ── Relation Managers ─────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            ClientesRelationManager::class,
        ];
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRutasCobro::route('/'),
            'create' => Pages\CreateRutaCobro::route('/create'),
            'edit'   => Pages\EditRutaCobro::route('/{record}/edit'),
        ];
    }
}
