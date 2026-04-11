<?php

namespace App\Filament\Widgets;

use App\Models\Venta;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class VentasMensualesChart extends ChartWidget
{
    protected ?string $heading = 'Ventas de los Últimos 30 Días';
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 2;
    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $dias    = collect();
        $totales = collect();

        for ($i = 29; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i)->startOfDay();
            $dias->push($fecha->format('d/m'));

            $total = Venta::whereDate('fecha_venta', $fecha)->sum('total');
            $totales->push((float) $total);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Ventas ($)',
                    'data'            => $totales->toArray(),
                    'borderColor'     => 'rgba(245, 158, 11, 1)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 3,
                ],
            ],
            'labels' => $dias->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
