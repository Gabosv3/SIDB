<?php

namespace App\Filament\Pages;

use App\Models\Cobrador;
use App\Services\ReporteCobrosService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class ReportesCobros extends Page
{
    protected static ?string $navigationLabel = 'Reportes de Cobros';
    protected static ?string $title = 'Reportes de Cobros';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.reportes-cobros';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $periodoTipo = 'semana';
    public string $fechaReferencia = '';
    /** @var array<int> */
    public array $cobradorIds = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-presentation-chart-line';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public function mount(): void
    {
        $this->fechaReferencia = today()->toDateString();
    }

    public function getCobradores(): \Illuminate\Support\Collection
    {
        return Cobrador::where('activo', true)
            ->where('excluir_reportes', false)
            ->orderBy('nombre')
            ->get();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function getRango(): array
    {
        return ReporteCobrosService::rango($this->periodoTipo, $this->fechaReferencia);
    }

    public function getEtiquetaPeriodo(): string
    {
        [$inicio, $fin] = $this->getRango();

        return $inicio->format('d/m/Y').' — '.$fin->format('d/m/Y');
    }

    public function getResumen(): array
    {
        [$inicio, $fin] = $this->getRango();

        return ReporteCobrosService::resumenPorCobrador($inicio, $fin, $this->cobradorIds);
    }

    public function getTotales(array $resumen): array
    {
        return ReporteCobrosService::totales($resumen);
    }

    public function getTendencia(): array
    {
        [$inicio, $fin] = $this->getRango();

        return ReporteCobrosService::tendencia($inicio, $fin, $this->cobradorIds);
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
