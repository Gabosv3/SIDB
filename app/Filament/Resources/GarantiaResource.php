<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GarantiaResource\Pages;
use App\Models\Garantia;
use App\Models\User;
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

class GarantiaResource extends Resource
{
    protected static ?string $model = Garantia::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shield-exclamation';
    }

    public static function getNavigationLabel(): string
    {
        return 'Garantías';
    }

    public static function getModelLabel(): string
    {
        return 'Garantía';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Garantías';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public static function getNavigationSort(): ?int
    {
        return 11;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Garantia::where('estado', 'pendiente')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Garantías pendientes de asignar';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Garantía')
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
                            Venta::where(fn ($q) => $q
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
                                    (string) $v->id => "{$v->numero_venta} — " . ($v->cliente?->nombre_completo ?? ''),
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

                    Forms\Components\Select::make('asignado_a')
                        ->label('Asignado a (técnico/encargado)')
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Sin asignar todavía'),

                    Forms\Components\Hidden::make('cliente_id'),
                    Forms\Components\Hidden::make('sucursal_id'),
                    Forms\Components\Hidden::make('reportado_por')
                        ->default(fn () => auth()->id()),

                    Forms\Components\DatePicker::make('fecha_reporte')
                        ->label('Fecha del reporte')
                        ->default(today())
                        ->required(),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options([
                            'pendiente'  => 'Pendiente',
                            'en_proceso' => 'En proceso',
                            'resuelta'   => 'Resuelta',
                            'rechazada'  => 'Rechazada',
                        ])
                        ->default('pendiente')
                        ->required()
                        ->reactive(),

                    Forms\Components\DatePicker::make('fecha_resolucion')
                        ->label('Fecha de resolución')
                        ->visible(fn (Get $get) => in_array($get('estado'), ['resuelta', 'rechazada']))
                        ->nullable(),
                ]),

            Section::make('Detalles')
                ->columns(1)
                ->components([
                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción del problema')
                        ->rows(3)
                        ->required(),

                    Forms\Components\Textarea::make('resolucion')
                        ->label('Resolución (qué se hizo — reparación, cambio, rechazo, etc.)')
                        ->rows(3)
                        ->visible(fn (Get $get) => in_array($get('estado'), ['resuelta', 'rechazada'])),
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

                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) =>
                        trim(($record->cliente?->nombre ?? '').' '.($record->cliente?->apellido ?? ''))
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

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Problema')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->descripcion),

                Tables\Columns\TextColumn::make('reportadoPor.name')
                    ->label('Reportado por')
                    ->placeholder('—')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('asignadoA.name')
                    ->label('Asignado a')
                    ->placeholder('— Sin asignar')
                    ->color(fn ($record) => $record->asignado_a ? null : 'danger'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'  => 'warning',
                        'en_proceso' => 'info',
                        'resuelta'   => 'success',
                        'rechazada'  => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente'  => 'Pendiente',
                        'en_proceso' => 'En proceso',
                        'resuelta'   => 'Resuelta',
                        'rechazada'  => 'Rechazada',
                        default      => $state,
                    }),

                Tables\Columns\TextColumn::make('fecha_reporte')
                    ->label('Reportada')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_resolucion')
                    ->label('Resuelta')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('sin_asignar')
                    ->label('Sin asignar')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNull('asignado_a')),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'en_proceso' => 'En proceso',
                        'resuelta'   => 'Resuelta',
                        'rechazada'  => 'Rechazada',
                    ]),

                Tables\Filters\Filter::make('activas')
                    ->label('Solo activas (ocultar resueltas/rechazadas)')
                    ->toggle()
                    ->default()
                    ->query(fn (Builder $query) => $query->whereIn('estado', ['pendiente', 'en_proceso'])),

                Tables\Filters\SelectFilter::make('asignado_a')
                    ->label('Asignado a')
                    ->relationship('asignadoA', 'name')
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('fecha_reporte', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGarantias::route('/'),
            'create' => Pages\CreateGarantia::route('/create'),
            'edit'   => Pages\EditGarantia::route('/{record}/edit'),
        ];
    }
}
