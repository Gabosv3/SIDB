<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaResource\Pages;
use App\Models\Categoria;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Categoria::class;

    // Categorías son por sucursal (isScopedToTenant = true por defecto)

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationLabel(): string
    {
        return 'Categorías';
    }

    public static function getModelLabel(): string
    {
        return 'Categoría';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Categorías';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Inventario';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos de la Categoría')
                ->description('Información básica de la categoría de productos')
                ->icon('heroicon-m-tag')
                ->columns(2)
                ->components([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(100)
                        ->unique(Categoria::class, 'nombre', ignoreRecord: true)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('icono')
                        ->label('Ícono (heroicon)')
                        ->placeholder('Ej: heroicon-o-cube')
                        ->maxLength(60)
                        ->helperText('Nombre del ícono heroicon a usar'),

                    Forms\Components\Toggle::make('activo')
                        ->label('Activa')
                        ->default(true),

                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción')
                        ->placeholder('Descripción breve de la categoría...')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('productos_count')
                    ->label('Productos')
                    ->counts('productos')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
            ])
            ->actions([
                Actions\EditAction::make(),
                // categoria_id en productos es nullOnDelete: no truena, pero
                // borrar una categoría con productos los deja sin categoría
                // de golpe y sin aviso. Se avisa antes en vez de dejarlo pasar.
                Actions\DeleteAction::make()
                    ->before(function (\App\Models\Categoria $record, Actions\DeleteAction $action) {
                        $productos = \App\Models\Producto::where('categoria_id', $record->id)->count();

                        if ($productos > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar')
                                ->body("Esta categoría tiene {$productos} producto(s) asignado(s) -- borrarla los dejaría sin categoría. Cambia esos productos de categoría primero, o desactívala en vez de eliminarla.")
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
                            $conProductos = $records->filter(fn (\App\Models\Categoria $c) => \App\Models\Producto::where('categoria_id', $c->id)->exists());

                            if ($conProductos->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No se puede eliminar')
                                    ->body('Algunas categorías seleccionadas tienen productos asignados: '.$conProductos->pluck('nombre')->join(', ').'.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategorias::route('/'),
            'create' => Pages\CreateCategoria::route('/create'),
            'edit'   => Pages\EditCategoria::route('/{record}/edit'),
        ];
    }
}