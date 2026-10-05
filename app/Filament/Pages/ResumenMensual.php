<?php

namespace App\Filament\Pages;

use App\Services\ResumenMensualService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class ResumenMensual extends Page
{
    protected static ?string $navigationLabel = 'Resumen del Mes';
    protected static ?string $title = 'Resumen Mensual';
    protected string $view = 'filament.pages.resumen-mensual';
    protected Width|string|null $maxContentWidth = Width::Full;

    /** Mes elegido, formato AAAA-MM. */
    public string $mes = '';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Resúmenes';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public function mount(): void
    {
        $this->mes = today()->format('Y-m');
    }

    private function fechaMes(): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $this->mes . '-01')->startOfMonth();
        } catch (\Throwable) {
            return today()->startOfMonth();
        }
    }

    public function getResumen(): array
    {
        return ResumenMensualService::calcular($this->fechaMes(), Filament::getTenant()?->id);
    }

    public function getPdfUrl(): string
    {
        return route('reporte.resumen-mensual', ['tenant' => Filament::getTenant()?->id ?? 1, 'mes' => $this->fechaMes()->format('Y-m')]);
    }
}
