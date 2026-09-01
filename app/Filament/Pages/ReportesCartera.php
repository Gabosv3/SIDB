<?php

namespace App\Filament\Pages;

use App\Services\ReporteCarteraService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ReportesCartera extends Page
{
    protected static ?string $navigationLabel = 'Reportes de Cartera';
    protected static ?string $title = 'Reportes de Cartera y Morosidad';
    protected static ?int $navigationSort = 7;
    protected string $view = 'filament.pages.reportes-cartera';
    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-exclamation-triangle';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public function getTotales(): array
    {
        return ReporteCarteraService::totalesEstaticos();
    }

    public function getAntiguedad(): array
    {
        return ReporteCarteraService::antiguedad();
    }

    public function getResumenPorRuta(): array
    {
        return ReporteCarteraService::resumenPorRuta();
    }

    public function getClientesMasAtrasados(): array
    {
        return ReporteCarteraService::clientesMasAtrasados();
    }
}
