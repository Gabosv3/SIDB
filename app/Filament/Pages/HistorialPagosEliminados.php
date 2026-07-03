<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class HistorialPagosEliminados extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Pagos Eliminados';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Historial de Pagos Eliminados';
    protected string $view = 'filament.pages.historial-pagos-eliminados';
    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-receipt-refund';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Historial';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->where('log_name', 'pagos_eliminados')->with('causer')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Eliminado por')
                    ->default('Sistema')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('clientes')
                    ->label('Cliente(s)')
                    ->getStateUsing(fn (Activity $record) => collect($record->properties->get('pagos_eliminados', []))
                        ->pluck('cliente_nombre')
                        ->filter()
                        ->unique()
                        ->implode(', ') ?: '—')
                    ->wrap(),

                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Pagos')
                    ->getStateUsing(fn (Activity $record) => count($record->properties->get('pagos_eliminados', []))),

                Tables\Columns\TextColumn::make('monto_total')
                    ->label('Monto total')
                    ->getStateUsing(fn (Activity $record) => '$' . number_format((float) $record->properties->get('monto_total', 0), 2))
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('motivo')
                    ->label('Motivo')
                    ->getStateUsing(fn (Activity $record) => $record->properties->get('motivo') ?: '—')
                    ->wrap()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\Filter::make('hoy')
                    ->label('Hoy')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Action::make('ver_detalle')
                    ->label('Ver detalle')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->modalHeading('Detalle de pagos eliminados')
                    ->modalContent(fn (Activity $record): \Illuminate\Contracts\View\View => view(
                        'filament.pages.historial-pagos-eliminados-detalle',
                        ['pagos' => collect($record->properties->get('pagos_eliminados', []))]
                    )),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
