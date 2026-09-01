<?php

namespace App\Filament\Pages;

use App\Models\Proveedor;
use App\Services\ReporteComprasService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class ReportesCompras extends Page
{
    protected static ?string $navigationLabel = 'Reportes de Compras';
    protected static ?string $title = 'Reportes de Compras y Proveedores';
    protected static ?int $navigationSort = 8;
    protected string $view = 'filament.pages.reportes-compras';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $periodoTipo = 'mes';
    public string $fechaReferencia = '';
    /** @var array<int> */
    public array $proveedorIds = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Compras';
    }

    public function mount(): void
    {
        $this->fechaReferencia = today()->toDateString();
    }

    public function getProveedores(): \Illuminate\Support\Collection
    {
        return Proveedor::where('activo', true)->orderBy('nombre')->get();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function getRango(): array
    {
        return ReporteComprasService::rango($this->periodoTipo, $this->fechaReferencia);
    }

    public function getEtiquetaPeriodo(): string
    {
        [$inicio, $fin] = $this->getRango();

        return $inicio->format('d/m/Y').' — '.$fin->format('d/m/Y');
    }

    public function getResumen(): array
    {
        [$inicio, $fin] = $this->getRango();

        return ReporteComprasService::resumenPorProveedor($inicio, $fin, $this->proveedorIds);
    }

    public function getTotales(array $resumen): array
    {
        return ReporteComprasService::totales($resumen);
    }

    public function getTopProductos(): array
    {
        [$inicio, $fin] = $this->getRango();

        return ReporteComprasService::topProductos($inicio, $fin, $this->proveedorIds);
    }

    public function getTendencia(): array
    {
        [$inicio, $fin] = $this->getRango();

        return ReporteComprasService::tendencia($inicio, $fin, $this->proveedorIds);
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
