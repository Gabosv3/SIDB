<?php

namespace App\Filament\Pages;

use App\Services\ReporteComisionesService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class ReportesComisiones extends Page
{
    protected static ?string $navigationLabel = 'Reportes de Comisiones';
    protected static ?string $title = 'Reportes de Comisiones y Nómina';
    protected static ?int $navigationSort = 9;
    protected string $view = 'filament.pages.reportes-comisiones';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $periodoTipo = 'quincena';
    public string $fechaReferencia = '';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Empleados';
    }

    public function mount(): void
    {
        $this->fechaReferencia = today()->toDateString();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function getRango(): array
    {
        return ReporteComisionesService::rango($this->periodoTipo, $this->fechaReferencia);
    }

    public function getEtiquetaPeriodo(): string
    {
        [$inicio, $fin] = $this->getRango();

        return $inicio->format('d/m/Y').' — '.$fin->format('d/m/Y');
    }

    public function getResumen(): array
    {
        [$inicio, $fin] = $this->getRango();

        return ReporteComisionesService::resumenPorEmpleado($inicio, $fin);
    }

    public function getTotales(array $resumen): array
    {
        return ReporteComisionesService::totales($resumen);
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
