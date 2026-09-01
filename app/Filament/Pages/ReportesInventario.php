<?php

namespace App\Filament\Pages;

use App\Services\ReporteInventarioService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ReportesInventario extends Page
{
    protected static ?string $navigationLabel = 'Reportes de Inventario';
    protected static ?string $title = 'Reportes de Inventario';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.reportes-inventario';
    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-archive-box';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Inventario';
    }

    public function getTotales(): array
    {
        return ReporteInventarioService::totales();
    }

    public function getStockBajo(): array
    {
        return ReporteInventarioService::stockBajo();
    }

    public function getMayorValorizacion(): array
    {
        return ReporteInventarioService::mayorValorizacion();
    }

    public function getSinMovimiento(): array
    {
        return ReporteInventarioService::sinMovimiento();
    }
}
