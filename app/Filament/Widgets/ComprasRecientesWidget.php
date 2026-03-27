<?php

namespace App\Filament\Widgets;

use App\Models\Compra;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ComprasRecientesWidget extends TableWidget
{
    protected static ?string $heading = 'Compras Recientes';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder|Relation|null
    {
        return Compra::orderBy('fecha_compra', 'desc')->limit(10);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_compra')
                    ->label('Compra')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->limit(20),

                Tables\Columns\TextColumn::make('fecha_compra')
                    ->label('Fecha')
                    ->dateTime('d/m/Y'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('MXN'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'  => 'warning',
                        'recibida'   => 'info',
                        'completada' => 'success',
                        'cancelada'  => 'danger',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo')
                    ->money('MXN')
                    ->color(fn (string $state): string =>
                        (float)$state > 0 ? 'danger' : 'success'
                    ),
            ])
            ->paginated(false);
    }
}

