<?php

namespace App\Filament\Pages;

use App\Models\Cobrador;
use App\Models\Supervision;
use App\Models\Supervisor;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Supervisiones extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Supervisiones';
    protected static ?string $title = 'Supervisiones de Cobradores';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.supervisiones';
    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Supervision::query()->with(['supervisor', 'cobrador', 'rutaCobro']))
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('supervisor_nombre')
                    ->label('Supervisor')
                    ->state(fn (Supervision $record) => $record->supervisor?->nombre_completo ?? '—')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'supervisor',
                        fn ($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('apellido', 'like', "%{$search}%")
                    )),

                Tables\Columns\TextColumn::make('cobrador_nombre')
                    ->label('Cobrador evaluado')
                    ->state(fn (Supervision $record) => $record->cobrador?->nombre_completo ?? '—')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'cobrador',
                        fn ($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('apellido', 'like', "%{$search}%")
                    )),

                Tables\Columns\TextColumn::make('rutaCobro.nombre')
                    ->label('Ruta'),

                Tables\Columns\TextColumn::make('calificacion')
                    ->label('Calificación')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->sortable(),

                Tables\Columns\IconColumn::make('visito_clientes_correctos')
                    ->label('Visitó clientes correctos')
                    ->boolean()
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('efectivo_cuadrado')
                    ->label('Efectivo cuadrado')
                    ->boolean()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->wrap()
                    ->limit(80)
                    ->placeholder('—')
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supervisor_id')
                    ->label('Supervisor')
                    ->options(fn () => Supervisor::where('activo', true)->get()->mapWithKeys(fn ($s) => [(string) $s->id => "{$s->nombre} {$s->apellido}"])),

                Tables\Filters\SelectFilter::make('cobrador_id')
                    ->label('Cobrador evaluado')
                    ->options(fn () => Cobrador::where('activo', true)->get()->mapWithKeys(fn ($c) => [(string) $c->id => "{$c->nombre} {$c->apellido}"])),

                Tables\Filters\Filter::make('calificacion_baja')
                    ->label('Calificación baja (1-2 ★)')
                    ->toggle()
                    ->query(fn ($query) => $query->where('calificacion', '<=', 2)),

                Tables\Filters\Filter::make('hoy')
                    ->label('Hoy')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('fecha', today())),
            ])
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
