<?php

namespace App\Filament\Pages;

use App\Models\Vendedor;
use App\Services\ReporteVentasService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class ReportesVentas extends Page
{
    protected static ?string $navigationLabel = 'Reportes de Ventas';
    protected static ?string $title = 'Reportes de Ventas';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.reportes-ventas';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $periodoTipo = 'semana';
    public string $fechaReferencia = '';
    /** @var array<int> */
    public array $vendedorIds = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Ventas';
    }

    public function mount(): void
    {
        $this->fechaReferencia = today()->toDateString();
    }

    public function getVendedores(): \Illuminate\Support\Collection
    {
        return Vendedor::where('activo', true)->orderBy('nombre')->get();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function getRango(): array
    {
        return ReporteVentasService::rango($this->periodoTipo, $this->fechaReferencia);
    }

    public function getEtiquetaPeriodo(): string
    {
        [$inicio, $fin] = $this->getRango();

        return $inicio->format('d/m/Y').' — '.$fin->format('d/m/Y');
    }

    public function getResumen(): array
    {
        [$inicio, $fin] = $this->getRango();

        return ReporteVentasService::resumenPorVendedor($inicio, $fin, $this->vendedorIds);
    }

    public function getTotales(array $resumen): array
    {
        return ReporteVentasService::totales($resumen);
    }

    public function getTopProductos(): array
    {
        [$inicio, $fin] = $this->getRango();

        return ReporteVentasService::topProductos($inicio, $fin, $this->vendedorIds);
    }

    public function getTendencia(): array
    {
        [$inicio, $fin] = $this->getRango();

        return ReporteVentasService::tendencia($inicio, $fin, $this->vendedorIds);
    }

    public function irPeriodoAnterior(): void
    {
        $this->fechaReferencia = match ($this->periodoTipo) {
            'semana' => Carbon::parse($this->fechaReferencia)->subWeek()->toDateString(),
            'quincena' => Carbon::parse($this->fechaReferencia)->subDays(15)->toDateString(),
            'mes' => Carbon::parse($this->fechaReferencia)->subMonthNoOverflow()->toDateString(),
            default => $this->fechaReferencia,
        };
    }

    public function irPeriodoSiguiente(): void
    {
        $this->fechaReferencia = match ($this->periodoTipo) {
            'semana' => Carbon::parse($this->fechaReferencia)->addWeek()->toDateString(),
            'quincena' => Carbon::parse($this->fechaReferencia)->addDays(15)->toDateString(),
            'mes' => Carbon::parse($this->fechaReferencia)->addMonthNoOverflow()->toDateString(),
            default => $this->fechaReferencia,
        };
    }
}
