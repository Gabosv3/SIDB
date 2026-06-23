<?php

namespace App\Filament\Pages;

use App\Models\Cobrador;
use App\Services\ResumenCobrosDiaService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ResumenCobrosDia extends Page
{
    protected static ?string $navigationLabel = 'Resumen del Día';
    protected static ?string $title = 'Resumen de Cobros del Día';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.resumen-cobros-dia';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $fecha = '';
    public ?int $cobrador_id = null;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public function mount(): void
    {
        $this->fecha = today()->toDateString();
    }

    public function getCobradores(): \Illuminate\Support\Collection
    {
        return Cobrador::where('activo', true)->orderBy('nombre')->get();
    }

    public function getResumen(): array
    {
        return ResumenCobrosDiaService::resumen($this->fecha, $this->cobrador_id);
    }

    public function getTotalesGenerales(array $resumen): array
    {
        return ResumenCobrosDiaService::totales($resumen);
    }
}
