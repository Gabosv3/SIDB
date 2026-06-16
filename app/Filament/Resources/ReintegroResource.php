<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReintegroResource\Pages;
use App\Models\Reintegro;
use App\Models\Vendedor;
use App\Models\Venta;
use Filament\Actions;
use Filament\Forms;
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
                    ->default()
                    ->query(fn ($query) => $query->whereNull('vendedor_id')),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente'     => 'Pendiente',
                        'en_proceso'    => 'En proceso',
                        'recuperado'    => 'Recuperado',
                        'no_recuperado' => 'No recuperado',
                    ]),

                Tables\Filters\Filter::make('activos')
                    ->label('Solo activos')
                    ->toggle()
                    ->query(fn ($query) => $query->whereIn('estado', ['pendiente', 'en_proceso'])),

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
