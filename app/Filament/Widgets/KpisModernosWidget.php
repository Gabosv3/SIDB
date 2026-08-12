<?php

namespace App\Filament\Widgets;

use App\Models\Cliente;
use App\Models\CuentaCobrar;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\Producto;
use App\Models\Venta;
use Filament\Widgets\Widget;

class KpisModernosWidget extends Widget
{
    protected static ?int $sort = 0;
    protected int|string|array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';
    protected string $view = 'filament.widgets.kpis-modernos-widget';

    protected function getViewData(): array
    {
        $hoy = today();
        $inicioMes = $hoy->copy()->startOfMonth();

        $ventasHoy = (float) Venta::whereDate('fecha_venta', $hoy)->sum('total');
        $ventasAyer = (float) Venta::whereDate('fecha_venta', $hoy->copy()->subDay())->sum('total');
        $ventasMes = (float) Venta::where('fecha_venta', '>=', $inicioMes)->sum('total');

        $cobradoHoy = (float) PagoVenta::whereDate('fecha_pago', $hoy)->whereNull('anulado_en')->sum('monto');
        $cobradoAyer = (float) PagoVenta::whereDate('fecha_pago', $hoy->copy()->subDay())->whereNull('anulado_en')->sum('monto');

        $cuentasPorCobrar = (float) CuentaCobrar::whereIn('estado', ['pendiente', 'vencido'])->sum('monto');

        $moraQuery = GestionCobro::whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
            ->whereDate('fecha_vencimiento', '<', $hoy);
        $morosidadMonto = (float) (clone $moraQuery)->selectRaw('SUM(monto_cuota - monto_pagado) AS total')->value('total');
        $morosidadCuotas = (clone $moraQuery)->count();

        $clientesActivos = Cliente::where('activo', true)->count();

        $productosStockBajo = Producto::whereColumn('stock', '<=', 'stock_minimo')->where('activo', true)->count();

        $ventasCanceladasMes = Venta::whereIn('estado', ['cancelada', 'devuelta'])
            ->where('fecha_venta', '>=', $inicioMes)
            ->count();

        return [
            'kpis' => [
                [
                    'label' => 'Ventas hoy',
                    'valor' => '$' . number_format($ventasHoy, 2),
                    'sub' => 'Ayer: $' . number_format($ventasAyer, 2),
                    'tendencia' => $ventasHoy >= $ventasAyer ? 'up' : 'down',
                    'icono' => 'heroicon-o-currency-dollar',
                    'color' => 'emerald',
                ],
                [
                    'label' => 'Ventas este mes',
                    'valor' => '$' . number_format($ventasMes, 2),
                    'sub' => 'Desde el ' . $inicioMes->format('d/m'),
                    'tendencia' => null,
                    'icono' => 'heroicon-o-chart-bar',
                    'color' => 'indigo',
                ],
                [
                    'label' => 'Cobrado hoy',
                    'valor' => '$' . number_format($cobradoHoy, 2),
                    'sub' => 'Ayer: $' . number_format($cobradoAyer, 2),
                    'tendencia' => $cobradoHoy >= $cobradoAyer ? 'up' : 'down',
                    'icono' => 'heroicon-o-banknotes',
                    'color' => 'sky',
                ],
                [
                    'label' => 'Cuentas por cobrar',
                    'valor' => '$' . number_format($cuentasPorCobrar, 2),
                    'sub' => 'Saldo pendiente de clientes',
                    'tendencia' => null,
                    'icono' => 'heroicon-o-credit-card',
                    'color' => 'amber',
                ],
                [
                    'label' => 'Morosidad',
                    'valor' => '$' . number_format($morosidadMonto, 2),
                    'sub' => $morosidadCuotas . ' cuota(s) vencida(s)',
                    'tendencia' => null,
                    'icono' => 'heroicon-o-exclamation-triangle',
                    'color' => 'rose',
                ],
                [
                    'label' => 'Clientes activos',
                    'valor' => (string) $clientesActivos,
                    'sub' => 'Registrados en el sistema',
                    'tendencia' => null,
                    'icono' => 'heroicon-o-users',
                    'color' => 'violet',
                ],
                [
                    'label' => 'Stock bajo',
                    'valor' => (string) $productosStockBajo,
                    'sub' => 'Productos requieren reposición',
                    'tendencia' => null,
                    'icono' => 'heroicon-o-archive-box',
                    'color' => $productosStockBajo > 0 ? 'orange' : 'emerald',
                ],
                [
                    'label' => 'Ventas canceladas',
                    'valor' => (string) $ventasCanceladasMes,
                    'sub' => 'Este mes',
                    'tendencia' => null,
                    'icono' => 'heroicon-o-x-circle',
                    'color' => $ventasCanceladasMes > 0 ? 'red' : 'emerald',
                ],
            ],
            'actualizadoEn' => now()->format('H:i:s'),
        ];
    }
}
