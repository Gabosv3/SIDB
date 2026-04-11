<?php

namespace App\Filament\Widgets;

use App\Models\CuentaCobrar;
use App\Models\Venta;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class VentasRecientesWidget extends TableWidget
{
    protected static ?string $heading = 'Ventas Recientes';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';

    protected function getTableQuery(): Builder|Relation|null
    {
        return Venta::with(['cliente'])
            ->orderBy('fecha_venta', 'desc')
            ->limit(10);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_venta')
                    ->label('Venta')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable(['clientes.nombre', 'clientes.apellido'])
                    ->limit(25),

                Tables\Columns\TextColumn::make('fecha_venta')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_pago')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'contado' => 'success',
                        'credito' => 'warning',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completada' => 'success',
                        'pendiente'  => 'warning',
                        'cancelada'  => 'danger',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo')
                    ->money('USD')
                    ->color(fn ($state): string => (float)$state > 0 ? 'danger' : 'success'),
            ])
            ->paginated(false);
    }
}
