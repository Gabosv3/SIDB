<?php

namespace App\Filament\Pages;

use App\Models\PagoVenta;
use App\Models\PosDevice;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class MonitorPos extends Page
{
    protected static ?string $navigationLabel = 'Monitor POS';
    protected static ?string $title           = 'Monitoreo de POS';
    protected static ?int    $navigationSort  = 1;
    protected string $view = 'filament.pages.monitor-pos';
    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-device-phone-mobile';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'POS';
    }

    public function getDevices(): Collection
    {
        return PosDevice::with(['cobrador', 'user'])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    public function getVentasHoy(): float
    {
        return (float) PagoVenta::whereDate('fecha_pago', today())->sum('monto');
    }

    public function getCobrosDia(): int
    {
        return PagoVenta::whereDate('fecha_pago', today())->distinct('cliente_id')->count('cliente_id');
    }
}
