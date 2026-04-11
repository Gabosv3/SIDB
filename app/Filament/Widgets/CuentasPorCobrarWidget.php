<?php

namespace App\Filament\Widgets;

use App\Models\CuentaCobrar;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class CuentasPorCobrarWidget extends TableWidget
{
    protected static ?string $heading = 'Cuentas por Cobrar (Top 10 Pendientes)';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 'full';
    protected ?string $pollingInterval = '120s';

    protected function getTableQuery(): Builder|Relation|null
    {
        return CuentaCobrar::with('cliente')
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->orderBy('monto', 'desc')
            ->limit(10);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable(['clientes.nombre', 'clientes.apellido'])
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('concepto')
                    ->label('Concepto')
                    ->limit(30)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('USD')
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->color(fn ($state): string =>
                        $state && now()->gt($state) ? 'danger' : 'warning'
                    )
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pagado'    => 'success',
                        'pendiente' => 'warning',
                        'vencido'   => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->emptyStateHeading('Sin cuentas pendientes')
            ->emptyStateDescription('Todas las cuentas están al día.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated(false);
    }
}
