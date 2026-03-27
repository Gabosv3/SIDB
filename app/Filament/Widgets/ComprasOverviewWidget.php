<?php

namespace App\Filament\Widgets;

use App\Models\Compra;
use App\Models\Proveedor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ComprasOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $comprasDelMes = Compra::where('fecha_compra', '>=', now()->startOfMonth())->get();
        $deudaPendiente = Compra::where('saldo_pendiente', '>', 0)->sum('saldo_pendiente');
        $proveedoresActivos = Proveedor::where('activo', true)->count();
        $comprasRecientes = Compra::where('fecha_compra', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Deuda Total', '$' . Number::format($deudaPendiente, 2))
                ->description('Saldo pendiente de pagar')
                ->icon('heroicon-m-banknotes')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/compras?tableFilters[saldo_pendiente][value]=true'),

            Stat::make('Compras Este Mes', $comprasDelMes->count())
                ->description('Total: $' . Number::format($comprasDelMes->sum('total'), 2))
                ->icon('heroicon-m-shopping-cart')
                ->color('info'),

            Stat::make('Proveedores Activos', $proveedoresActivos)
                ->description('En la base de datos')
                ->icon('heroicon-m-building-storefront')
                ->color('success'),

            Stat::make('Compras Últimos 7 Días', $comprasRecientes)
                ->description('Período reciente')
                ->icon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
