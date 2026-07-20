<?php

namespace App\Filament\Resources\VehiculoResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class MantenimientosRelationManager extends RelationManager
{
    protected static string $relationship = 'mantenimientos';

    protected static ?string $title = 'Historial de mantenimiento';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mantenimiento')
                ->columns(2)
                ->components([
                    Forms\Components\DatePicker::make('fecha')
                        ->label('Fecha')
                        ->default(today())
                        ->required(),

                    Forms\Components\TextInput::make('kilometraje')
                        ->label('Kilometraje')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('km')
                        ->required(),

                    Forms\Components\Select::make('tipo')
                        ->label('Tipo de cambio')
                        ->options([
                            'aceite'  => 'Cambio de aceite',
                            'llantas' => 'Llantas',
                            'frenos'  => 'Frenos',
                            'bateria' => 'Batería',
                            'cadena'  => 'Cadena / kit de arrastre',
                            'otro'    => 'Otro',
                        ])
                        ->default('otro')
                        ->required(),

                    Forms\Components\TextInput::make('taller')
                        ->label('Taller / proveedor')
                        ->maxLength(150),

                    Forms\Components\TextInput::make('costo')
                        ->label('Costo')
                        ->prefix('$')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\TextInput::make('proximo_cambio_km')
                        ->label('Próximo cambio sugerido')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('km')
                        ->helperText('Kilometraje al que debería hacerse el siguiente cambio de este tipo.'),

                    Forms\Components\Textarea::make('descripcion')
                        ->label('Detalle')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('comprobante')
                        ->label('Factura / comprobante')
                        ->image()
                        ->imageEditor()
                        ->imagePreviewHeight('150')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('mantenimientos')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('kilometraje')
                    ->label('Kilometraje')
                    ->formatStateUsing(fn (int $state) => number_format($state) . ' km')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'aceite'  => 'Aceite',
                        'llantas' => 'Llantas',
                        'frenos'  => 'Frenos',
                        'bateria' => 'Batería',
                        'cadena'  => 'Cadena',
                        default   => 'Otro',
                    }),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Detalle')
                    ->limit(40)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('taller')
                    ->label('Taller')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('costo')
                    ->label('Costo')
                    ->money('USD')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('proximo_cambio_km')
                    ->label('Próximo cambio')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state) . ' km' : '—')
                    ->color('warning'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Registrar mantenimiento')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['registrado_por'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->defaultSort('kilometraje', 'desc');
    }
}
