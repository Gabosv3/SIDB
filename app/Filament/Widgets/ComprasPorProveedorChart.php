<?php

namespace App\Filament\Widgets;

use App\Models\Compra;
use App\Models\Proveedor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ComprasPorProveedorChart extends ChartWidget
{
    protected ?string $heading = 'Top Proveedores (Últimos 30 días)';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = Compra::where('fecha_compra', '>=', now()->subDays(30))
            ->with('proveedor')
            ->selectRaw('proveedor_id, COUNT(*) as count, SUM(total) as total')
            ->groupBy('proveedor_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $labels = $data->map(fn ($item) => $item->proveedor->nombre)->toArray();
        $values = $data->map(fn ($item) => (float)$item->total)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Monto Total',
                    'data' => $values,
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                        'rgba(255, 159, 64, 0.5)',
                        'rgba(201, 203, 207, 0.5)',
                    ],
                    'borderColor' => [
                        'rgb(54, 162, 235)',
                        'rgb(75, 192, 192)',
                        'rgb(153, 102, 255)',
                        'rgb(255, 159, 64)',
                        'rgb(201, 203, 207)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
