<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReintegroResource\Pages;
use App\Models\Reintegro;
use App\Models\Vendedor;
use App\Models\Venta;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReintegroResource extends Resource
{
    protected static ?string $model = Reintegro::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-arrow-uturn-left';
    }

    public static function getNavigationLabel(): string
    {
        return 'Reintegros';
    }

    public static function getModelLabel(): string
    {
        return 'Reintegro';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Reintegros';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Reintegro::whereNull('vendedor_id')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Reintegros sin asignar';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asignación')
                ->columns(2)
                ->components([
                    Forms\Components\Select::make('venta_id')
                        ->label('Venta')
                        ->relationship('venta', 'numero_venta')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->getOptionLabelFromRecordUsing(fn (Venta $venta) =>
                            "{$venta->numero_venta} — " . ($venta->cliente?->nombre_completo ?? 'Sin cliente')
                        )
                        ->getSearchResultsUsing(fn (string $search) =>
                            Venta::where('saldo_pendiente', '>', 0)
                                ->where(fn ($q) => $q
                                    ->where('numero_venta', 'like', "%{$search}%")
                                    ->orWhereHas('cliente', fn ($c) => $c
                                        ->where('nombre', 'like', "%{$search}%")
                                        ->orWhere('apellido', 'like', "%{$search}%")
                                        ->orWhere('codigo_anterior', 'like', "%{$search}%")
                                    )
                                )
                                ->with('cliente')
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($v) => [
                                    $v->id => "{$v->numero_venta} — " . ($v->cliente?->nombre_completo ?? ''),
                                ])
                        )
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (! $state) return;
                            $venta = Venta::with('cliente')->find($state);
                            if ($venta) {
                                $set('cliente_id', $venta->cliente_id);
                                $set('sucursal_id', $venta->sucursal_id);
                            }
                        }),

                    Forms\Components\Select::make('vendedor_id')
                        ->label('Vendedor asignado')
                        ->options(
                            Vendedor::where('activo', true)
                                ->orderBy('nombre')
                                ->get()
                                ->mapWithKeys(fn ($v) => [$v->id => "{$v->nombre} {$v->apellido} ({$v->codigo})"])
                        )
                        ->searchable()
                        ->required(),

                    Forms\Components\Hidden::make('cliente_id'),
                    Forms\Components\Hidden::make('sucursal_id'),

                    Forms\Components\Hidden::make('asignado_por')
                        ->default(fn () => auth()->id()),

                    Forms\Components\DatePicker::make('fecha_asignacion')
                        ->label('Fecha de asignación')
                        ->default(today())
                        ->required(),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options([
                            'pendiente'        => 'Pendiente',
                            'en_proceso'       => 'En proceso',
                            'recuperado'       => 'Recuperado',
                            'no_recuperado'    => 'No recuperado',
                        ])
                        ->default('pendiente')
                        ->required()
                        ->reactive(),

                    Forms\Components\DatePicker::make('fecha_recuperacion')
                        ->label('Fecha de recuperación')
                        ->visible(fn (Get $get) => in_array($get('estado'), ['recuperado', 'no_recuperado']))
                        ->nullable(),
                ]),

            Section::make('Detalles')
                ->columns(2)
                ->components([
                    Forms\Components\Textarea::make('motivo')
                        ->label('Motivo del reintegro')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('monto_adeudado')
                        ->label('Monto adeudado vencido')
                        ->prefix('$')
                        ->numeric()
                        ->disabled(),

                    Forms\Components\TextInput::make('cuotas_vencidas')
                        ->label('Cuotas vencidas')
                        ->numeric()
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venta.numero_venta')
                    ->label('Venta')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('cliente.codigo_anterior')
                    ->label('Código')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) =>
                        ($record->cliente?->nombre ?? '') . ' ' . ($record->cliente?->apellido ?? '')
                    )
                    ->searchable(query: fn (Builder $query, string $search) =>
                        $query->whereHas('cliente', fn ($q) =>
                            $q->where('nombre', 'like', "%{$search}%")
                              ->orWhere('apellido', 'like', "%{$search}%")
                              ->orWhere('codigo_anterior', 'like', "%{$search}%")
                        )
                    ),

                Tables\Columns\TextColumn::make('cliente.telefono_normal')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->icon('heroicon-m-phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cliente.direccion')
                    ->label('Dirección')
                    ->placeholder('—')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->cliente?->direccion)
                    ->toggleable(isToggledHiddenByDefault: true),

                // Ruta de la que se sacó al cliente al mandarlo a reintegro — NO la
                // ruta actual del cliente.rutaCobro, que siempre es null mientras el
                // reintegro sigue activo (por eso se saca de ahí, no del cliente).
                Tables\Columns\TextColumn::make('rutaCobroOriginal.nombre')
                    ->label('Ruta')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray')
                    ->searchable(query: fn (Builder $query, string $search) =>
                        $query->whereHas('rutaCobroOriginal', fn ($q) =>
                            $q->where('nombre', 'like', "%{$search}%")
                        )
                    ),

                Tables\Columns\TextColumn::make('asignadoPor.name')
                    ->label('Enviado por')
                    ->placeholder('—')
                    ->icon('heroicon-m-user')
                    ->tooltip('Cobrador (o admin) que registró este reintegro')
                    ->searchable(query: fn (Builder $query, string $search) =>
                        $query->whereHas('asignadoPor', fn ($q) =>
                            $q->where('name', 'like', "%{$search}%")
                        )
                    ),

                Tables\Columns\TextColumn::make('motivo')
                    ->label('Motivo')
                    ->placeholder('—')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->motivo)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vendedor.nombre')
                    ->label('Vendedor asignado')
                    ->formatStateUsing(fn ($record) => $record->vendedor
                        ? $record->vendedor->nombre . ' ' . $record->vendedor->apellido
                        : '— Sin asignar'
                    )
                    ->color(fn ($record) => $record->vendedor ? null : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'     => 'warning',
                        'en_proceso'    => 'info',
                        'recuperado'    => 'success',
                        'no_recuperado' => 'danger',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente'     => 'Pendiente',
                        'en_proceso'    => 'En proceso',
                        'recuperado'    => 'Recuperado',
                        'no_recuperado' => 'No recuperado',
                        default         => $state,
                    }),

                Tables\Columns\TextColumn::make('monto_adeudado')
                    ->label('Monto vencido')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cuotas_vencidas')
                    ->label('Cuotas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_asignacion')
                    ->label('Asignado')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_recuperacion')
                    ->label('Recuperado')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('sin_asignar')
                    ->label('Sin asignar')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNull('vendedor_id')),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente'     => 'Pendiente',
                        'en_proceso'    => 'En proceso',
                        'recuperado'    => 'Recuperado',
                        'no_recuperado' => 'No recuperado',
                    ]),

                // Por defecto solo se ven los reintegros en curso (pendiente/en_proceso)
                // y los ya resueltos que todavía no se han devuelto a una ruta (para no
                // perder de vista la acción "Devolver a ruta" pendiente). Los resueltos
                // y ya devueltos son el "historial" propiamente dicho, y quedan ocultos.
                Tables\Filters\Filter::make('activos')
                    ->label('Solo activos (ocultar historial)')
                    ->toggle()
                    ->default()
                    ->query(fn (Builder $query) => $query->where(function (Builder $q) {
                        $q->whereIn('estado', ['pendiente', 'en_proceso'])
                            ->orWhere(function (Builder $q2) {
                                $q2->whereIn('estado', ['recuperado', 'no_recuperado'])
                                    ->whereNotNull('ruta_cobro_id_original')
                                    ->whereHas('cliente', fn (Builder $c) => $c->whereNull('ruta_cobro_id'));
                            });
                    })),

                Tables\Filters\Filter::make('mes_actual')
                    ->label('Asignados este mes')
                    ->toggle()
                    ->query(fn ($query) => $query
                        ->whereMonth('fecha_asignacion', today()->month)
                        ->whereYear('fecha_asignacion', today()->year)
                    ),

                Tables\Filters\SelectFilter::make('vendedor_id')
                    ->label('Vendedor')
                    ->relationship('vendedor', 'nombre')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('asignado_por')
                    ->label('Enviado por')
                    ->relationship('asignadoPor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('ruta_cobro_id_original')
                    ->label('Ruta de origen')
                    ->relationship('rutaCobroOriginal', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\Action::make('devolverARuta')
                    ->label('Devolver a ruta')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (Reintegro $record) => $record->puedeDevolverseARuta())
                    ->requiresConfirmation()
                    ->modalHeading('Devolver cliente a su ruta de cobro')
                    ->modalDescription(fn (Reintegro $record) => sprintf(
                        'El cliente volverá a la ruta "%s", de la que se sacó al mandarlo a recoger.',
                        $record->rutaCobroOriginal?->nombre ?? '—'
                    ))
                    ->action(function (Reintegro $record) {
                        if ($record->devolverClienteARuta()) {
                            Notification::make()
                                ->title('Cliente devuelto a su ruta')
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('No se pudo devolver a la ruta')
                            ->body('El cliente ya tiene una ruta asignada, o todavía tiene otro reintegro sin resolver.')
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
            ->defaultSort('fecha_asignacion', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReintegros::route('/'),
            'create' => Pages\CreateReintegro::route('/create'),
            'edit'   => Pages\EditReintegro::route('/{record}/edit'),
        ];
    }
}
