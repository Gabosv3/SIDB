<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProductosBajoStockWidget extends TableWidget
{
    protected static ?string $heading = 'Productos con Stock Bajo';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';
    protected ?string $pollingInterval = '120s';

    protected function getTableQuery(): Builder|Relation|null
    {
        return Producto::whereColumn('stock', '<=', 'stock_minimo')
            ->where('activo', true)
            ->with('categoria')
            ->orderBy('stock');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Producto')
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->placeholder('—')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock Actual')
                    ->badge()
                    ->color(fn ($state): string => $state <= 0 ? 'danger' : 'warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('stock_minimo')
                    ->label('Stock Mín.')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('precio_venta')
                    ->label('Precio Venta')
                    ->money('USD'),
            ])
            ->emptyStateHeading('Sin productos bajo stock')
            ->emptyStateDescription('Todos los productos tienen stock suficiente.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated(false);
    }
}
