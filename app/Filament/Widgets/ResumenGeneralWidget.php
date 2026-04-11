<?php

namespace App\Filament\Widgets;

use App\Models\Cliente;
use App\Models\CuentaCobrar;
use App\Models\Producto;
use App\Models\Venta;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ResumenGeneralWidget extends BaseWidget
{
    protected static ?int $sort = 0;
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $ventasMes         = Venta::where('fecha_venta', '>=', now()->startOfMonth())->sum('total');
        $ventasAyer        = Venta::whereDate('fecha_venta', now()->subDay())->sum('total');
        $clientesActivos   = Cliente::where('activo', true)->count();
        $cuentasPendientes = CuentaCobrar::whereIn('estado', ['pendiente', 'vencido'])->sum('monto');
        $productosStockBajo = Producto::whereColumn('stock', '<=', 'stock_minimo')->where('activo', true)->count();
        $ventasHoy         = Venta::whereDate('fecha_venta', now())->sum('total');

        return [
            Stat::make('Ventas Este Mes', '$' . Number::format((float) $ventasMes, 2))
                ->description('Hoy: $' . Number::format((float) $ventasHoy, 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($ventasHoy >= $ventasAyer ? 'success' : 'warning')
                ->icon('heroicon-m-currency-dollar'),

            Stat::make('Cuentas por Cobrar', '$' . Number::format((float) $cuentasPendientes, 2))
                ->description('Saldo pendiente de clientes')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger')
                ->icon('heroicon-m-credit-card'),

            Stat::make('Clientes Activos', $clientesActivos)
                ->description('Registrados en el sistema')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->icon('heroicon-m-users'),

            Stat::make('Productos Bajo Stock', $productosStockBajo)
                ->description('Requieren reposición')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($productosStockBajo > 0 ? 'warning' : 'success')
                ->icon('heroicon-m-archive-box'),
        ];
    }
}
