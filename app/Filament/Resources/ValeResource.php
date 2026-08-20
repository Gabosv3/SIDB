<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValeResource\Pages;
use App\Models\Vale;
use App\Models\Vehiculo;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ValeResource extends Resource
{
    protected static ?string $model = Vale::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-receipt-percent';
    }

    public static function getNavigationLabel(): string
    {
        return 'Gastos';
    }

    public static function getModelLabel(): string
    {
        return 'Gasto';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Gastos';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Vehículos y Gastos';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Vale::where('estado', 'pendiente')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Gastos pendientes de aprobación';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Gasto')
                ->columns(2)
                ->components([
                    Forms\Components\Select::make('user_id')
                        ->label('Empleado')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('tipo')
                        ->label('Tipo de vale')
                        ->options([
                            'consumo'  => 'Consumo (cobrador/vendedor)',
                            'vehiculo' => 'Gasto de vehículo',
                        ])
                        ->required()
                        ->reactive(),

                    Forms\Components\Select::make('vehiculo_id')
                        ->label('Vehículo')
                        ->options(fn () => Vehiculo::orderBy('placa')->pluck('placa', 'id'))
                        ->searchable()
                        ->visible(fn (Get $get) => $get('tipo') === 'vehiculo')
                        ->required(fn (Get $get) => $get('tipo') === 'vehiculo'),

                    Forms\Components\Select::make('categoria_vehiculo')
                        ->label('Categoría de gasto')
                        ->options([
                            'gasolina'      => 'Gasolina',
                            'imprevisto'    => 'Imprevisto',
                            'mantenimiento' => 'Mantenimiento',
                        ])
                        ->visible(fn (Get $get) => $get('tipo') === 'vehiculo')
                        ->required(fn (Get $get) => $get('tipo') === 'vehiculo'),

                    Forms\Components\TextInput::make('monto')
                        ->label('Monto')
                        ->prefix('$')
                        ->numeric()
                        ->minValue(0.01)
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_gasto')
                        ->label('Fecha del gasto')
                        ->default(today())
                        ->required(),

                    Forms\Components\FileUpload::make('comprobante')
                        ->label('Comprobante')
                        ->image()
                        ->imageEditor()
                        ->imagePreviewHeight('180')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('vales')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('descuenta_cobro_diario')
                        ->label('Descontar del efectivo a entregar hoy')
                        ->helperText('Actívalo si es un gasto chico que el empleado ya pagó de lo cobrado ese día (imprevisto de calle, gasolina). Si es una reparación grande que paga la empresa aparte, déjalo apagado — no se resta del efectivo que debe entregar en Resumen del Día.')
                        ->default(false)
                        ->columnSpanFull(),
                ]),

            Section::make('Aprobación')
                ->columns(2)
                ->hidden(fn (string $operation) => $operation === 'create')
                ->components([
                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options([
                            'pendiente' => 'Pendiente',
                            'aprobado'  => 'Aprobado',
                            'rechazado' => 'Rechazado',
                        ])
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\Textarea::make('observaciones_admin')
                        ->label('Observaciones del administrador')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('comprobante')
                    ->label('Comprobante')
                    ->disk('public')
                    ->square()
                    ->size(50),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state) => $state === 'consumo' ? 'gray' : 'info')
                    ->formatStateUsing(fn (string $state) => $state === 'consumo' ? 'Consumo' : 'Vehículo'),

                Tables\Columns\TextColumn::make('categoria_vehiculo')
                    ->label('Categoría')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'gasolina'      => 'Gasolina',
                        'imprevisto'    => 'Imprevisto',
                        'mantenimiento' => 'Mantenimiento',
                        default         => '—',
                    }),

                Tables\Columns\TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\IconColumn::make('descuenta_cobro_diario')
                    ->label('Descuenta hoy')
                    ->boolean()
                    ->tooltip(fn (bool $state) => $state
                        ? 'Se resta del efectivo a entregar en Resumen del Día'
                        : 'No afecta el efectivo a entregar (gasto pagado aparte por la empresa)')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('fecha_gasto')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->description(fn (Vale $record) => $record->created_at?->format('H:i').' — hora de registro')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'aprobado'  => 'success',
                        'rechazado' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'aprobado'  => 'Aprobado',
                        'rechazado' => 'Rechazado',
                        default     => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'aprobado'  => 'Aprobado',
                        'rechazado' => 'Rechazado',
                    ])
                    ->default('pendiente'),

                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'consumo'  => 'Consumo',
                        'vehiculo' => 'Vehículo',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Empleado')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Vale $record) => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->action(function (Vale $record) {
                        $record->aprobar(auth()->user());

                        Notification::make()
                            ->title('Gasto aprobado')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Vale $record) => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('observaciones_admin')
                            ->label('Motivo del rechazo')
                            ->rows(2),
                    ])
                    ->action(function (Vale $record, array $data) {
                        $record->rechazar(auth()->user(), $data['observaciones_admin'] ?? null);

                        Notification::make()
                            ->title('Gasto rechazado')
                            ->warning()
                            ->send();
                    }),

                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVales::route('/'),
            'create' => Pages\CreateVale::route('/create'),
            'edit'   => Pages\EditVale::route('/{record}/edit'),
        ];
    }
}
