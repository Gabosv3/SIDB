<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoResource\Pages;
use App\Models\User;
use App\Models\Vehiculo;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VehiculoResource extends Resource
{
    protected static ?string $model = Vehiculo::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationLabel(): string
    {
        return 'Vehículos';
    }

    public static function getModelLabel(): string
    {
        return 'Vehículo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Vehículos';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Vehículos y Gastos';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del vehículo')
                ->columns(2)
                ->components([
                    Forms\Components\TextInput::make('placa')
                        ->label('Placa')
                        ->required()
                        ->unique(Vehiculo::class, 'placa', ignoreRecord: true)
                        ->maxLength(20),

                    Forms\Components\Select::make('tipo')
                        ->label('Tipo')
                        ->options([
                            'moto'   => 'Moto',
                            'carro'  => 'Carro',
                            'pickup' => 'Pickup',
                            'otro'   => 'Otro',
                        ])
                        ->default('moto')
                        ->required(),

                    Forms\Components\TextInput::make('marca')
                        ->label('Marca')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('modelo')
                        ->label('Modelo')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('anio')
                        ->label('Año')
                        ->numeric()
                        ->minValue(1980)
                        ->maxValue((int) date('Y') + 1),

                    Forms\Components\Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->relationship('sucursal', 'nombre')
                        ->searchable()
                        ->preload(),
                ]),

            Section::make('Asignación')
                ->description('Asignación fija a un vendedor/cobrador, o vehículo de reserva disponible para cuando el vehículo asignado a alguien se avería.')
                ->icon('heroicon-m-user-circle')
                ->columns(2)
                ->components([
                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options([
                            'activo'        => 'Activo (asignado)',
                            'reserva'       => 'De reserva',
                            'mantenimiento' => 'En mantenimiento',
                            'inactivo'      => 'Inactivo',
                        ])
                        ->default('activo')
                        ->required()
                        ->reactive(),

                    Forms\Components\Select::make('asignado_a')
                        ->label('Asignado a')
                        ->relationship(
                            name: 'asignadoA',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->where(fn ($q) => $q->whereHas('vendedor')->orWhereHas('cobrador')),
                        )
                        ->getOptionLabelFromRecordUsing(fn (User $u) => $u->name)
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Vacío si es un vehículo de reserva sin asignar todavía.'),

                    Forms\Components\Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('placa')
                    ->label('Placa')
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'moto'   => 'Moto',
                        'carro'  => 'Carro',
                        'pickup' => 'Pickup',
                        default  => 'Otro',
                    }),

                Tables\Columns\TextColumn::make('marca')
                    ->label('Marca / Modelo')
                    ->formatStateUsing(fn ($record) => trim("{$record->marca} {$record->modelo}"))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('asignadoA.name')
                    ->label('Asignado a')
                    ->placeholder('— Sin asignar')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo'        => 'success',
                        'reserva'       => 'info',
                        'mantenimiento' => 'warning',
                        'inactivo'      => 'danger',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'activo'        => 'Activo',
                        'reserva'       => 'De reserva',
                        'mantenimiento' => 'En mantenimiento',
                        'inactivo'      => 'Inactivo',
                        default         => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'activo'        => 'Activo',
                        'reserva'       => 'De reserva',
                        'mantenimiento' => 'En mantenimiento',
                        'inactivo'      => 'Inactivo',
                    ]),

                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'moto'   => 'Moto',
                        'carro'  => 'Carro',
                        'pickup' => 'Pickup',
                        'otro'   => 'Otro',
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
            ->defaultSort('placa');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVehiculos::route('/'),
            'create' => Pages\CreateVehiculo::route('/create'),
            'edit'   => Pages\EditVehiculo::route('/{record}/edit'),
        ];
    }
}
