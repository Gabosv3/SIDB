<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendedorResource\Pages;
use App\Models\Sucursal;
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
