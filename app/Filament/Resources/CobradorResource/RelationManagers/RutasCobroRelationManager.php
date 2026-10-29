<?php

namespace App\Filament\Resources\CobradorResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RutasCobroRelationManager extends RelationManager
{
    protected static string $relationship = 'rutasCobro';

    protected static ?string $recordTitleAttribute = 'nombre';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información de la ruta')
                ->icon('heroicon-m-map')
                ->columns(2)
                ->components([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre de la ruta')
                        ->placeholder('Ej: Ruta Centro')
                        ->required()
                        ->maxLength(150)
                        // La BD tiene un único (cobrador_id, nombre) que cuenta también las rutas borradas.
                        ->unique(
                            'rutas_cobro',
                            'nombre',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->where('cobrador_id', $this->getOwnerRecord()->id),
                        )
                        ->validationMessages(['unique' => 'Este cobrador ya tiene una ruta con ese nombre (incluye las rutas eliminadas).']),

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
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
}
