<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EncuestaClienteResource\Pages;
use App\Models\EncuestaCliente;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

// Solo lectura desde el panel — la encuesta la genera el supervisor desde la
// app en campo (contrasta lo que dice el cliente contra lo que el sistema
// tiene registrado). Aquí solo se revisan los casos con diferencia para
// investigar, no se crean encuestas nuevas manualmente.
class EncuestaClienteResource extends Resource
{
    protected static ?string $model = EncuestaCliente::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getNavigationLabel(): string
    {
        return 'Encuestas a clientes';
    }

    public static function getModelLabel(): string
    {
        return 'Encuesta a cliente';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Encuestas a clientes';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public static function getNavigationSort(): ?int
    {
        return 12;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = EncuestaCliente::where('resultado', '!=', 'coincide')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Encuestas con diferencia por investigar';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Encuesta')
                ->columns(2)
                ->components([
                    Forms\Components\Select::make('resultado')
                        ->label('Resultado')
                        ->options([
                            'coincide'                  => 'Todo coincide',
                            'diferencia_investigar'      => 'Diferencia por investigar',
                            'pago_no_registrado'         => 'Pago no registrado',
                            'comprobante_inconsistente'  => 'Comprobante inconsistente',
                        ])
                        ->required(),

                    Forms\Components\DatePicker::make('fecha')->label('Fecha')->required(),

                    Forms\Components\TextInput::make('monto_frecuencia_pago')
                        ->label('Monto/frecuencia que dice pagar el cliente'),

                    Forms\Components\TextInput::make('cobrador_reportado_cliente')
                        ->label('Cobrador que el cliente dice que lo visita'),

                    Forms\Components\Toggle::make('recibio_comprobante')
                        ->label('¿El cliente dice que recibe comprobante?'),

                    Forms\Components\TextInput::make('ultimo_pago_monto_cliente')
                        ->label('Último pago según el cliente')
                        ->numeric()->prefix('$'),

                    Forms\Components\DatePicker::make('ultimo_pago_fecha_cliente')
                        ->label('Fecha de ese pago según el cliente'),

                    Forms\Components\TextInput::make('saldo_informado_cliente')
                        ->label('Saldo que el cliente cree tener')
                        ->numeric()->prefix('$'),
                ]),

            Section::make('Verificación interna BM (no editable)')
                ->columns(3)
                ->components([
                    Forms\Components\TextInput::make('pago_registrado_bm')
                        ->label('Pago registrado en BM')->numeric()->prefix('$')->disabled(),
                    Forms\Components\TextInput::make('saldo_registrado_bm')
                        ->label('Saldo registrado en BM')->numeric()->prefix('$')->disabled(),
                    Forms\Components\TextInput::make('diferencia')
                        ->label('Diferencia')->numeric()->prefix('$')->disabled(),
                ]),

            Section::make('Observaciones')
                ->components([
                    Forms\Components\Textarea::make('observaciones')->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')->date('d/m/Y')->sortable(),

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

                Tables\Columns\TextColumn::make('cliente.codigo_anterior')
                    ->label('Código')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('supervisor.nombre')
                    ->label('Supervisor')
                    ->formatStateUsing(fn ($record) =>
                        trim(($record->supervisor?->nombre ?? '').' '.($record->supervisor?->apellido ?? ''))
                    ),

                Tables\Columns\TextColumn::make('cobrador.nombre')
                    ->label('Cobrador de la ruta')
                    ->formatStateUsing(fn ($record) =>
                        trim(($record->cobrador?->nombre ?? '').' '.($record->cobrador?->apellido ?? ''))
                    )
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('cobrador_reportado_cliente')
                    ->label('Cobrador que dice el cliente')
                    ->placeholder('—')
                    ->color(function ($record) {
                        $realNombre = trim(($record->cobrador?->nombre ?? '').' '.($record->cobrador?->apellido ?? ''));
                        return $record->cobrador_reportado_cliente && $realNombre
                            && trim($record->cobrador_reportado_cliente) !== $realNombre
                                ? 'danger' : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ultimo_pago_monto_cliente')
                    ->label('Dice el cliente')
                    ->money('USD')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('pago_registrado_bm')
                    ->label('Registrado en BM')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('diferencia')
                    ->label('Diferencia')
                    ->money('USD')
                    ->color(fn ($record) => (float) $record->diferencia !== 0.0 ? 'danger' : 'success')
                    ->weight(fn ($record) => (float) $record->diferencia !== 0.0 ? 'bold' : null),

                Tables\Columns\TextColumn::make('resultado')
                    ->label('Resultado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'coincide'                 => 'success',
                        'diferencia_investigar'     => 'warning',
                        'pago_no_registrado'        => 'danger',
                        'comprobante_inconsistente' => 'danger',
                        default                     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'coincide'                  => 'Todo coincide',
                        'diferencia_investigar'      => 'Diferencia por investigar',
                        'pago_no_registrado'         => 'Pago no registrado',
                        'comprobante_inconsistente'  => 'Comprobante inconsistente',
                        default                      => $state,
                    }),

                Tables\Columns\TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->observaciones)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('resultado')
                    ->label('Resultado')
                    ->options([
                        'coincide'                  => 'Todo coincide',
                        'diferencia_investigar'      => 'Diferencia por investigar',
                        'pago_no_registrado'         => 'Pago no registrado',
                        'comprobante_inconsistente'  => 'Comprobante inconsistente',
                    ]),

                Tables\Filters\Filter::make('con_diferencia')
                    ->label('Solo con diferencia (ocultar coincidencias)')
                    ->toggle()
                    ->default()
                    ->query(fn (Builder $query) => $query->where('resultado', '!=', 'coincide')),

                Tables\Filters\SelectFilter::make('supervisor_id')
                    ->label('Supervisor')
                    ->relationship('supervisor', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEncuestasCliente::route('/'),
            'view'   => Pages\ViewEncuestaCliente::route('/{record}'),
            'edit'   => Pages\EditEncuestaCliente::route('/{record}/edit'),
        ];
    }
}
