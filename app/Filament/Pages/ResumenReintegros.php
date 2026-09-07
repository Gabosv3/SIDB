<?php

namespace App\Filament\Pages;

use App\Models\Cobrador;
use App\Models\RutaCobro;
use App\Models\Vendedor;
use App\Services\ResumenReintegrosService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ResumenReintegros extends Page
{
    protected static ?string $navigationLabel = 'Reintegros del Día';
    protected static ?string $title = 'Resumen de Reintegros del Día';
    protected string $view = 'filament.pages.resumen-reintegros';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $fecha = '';
    /** @var array<int> */
    public array $vendedoresSeleccionados = [];
    /** @var array<int> */
    public array $cobradoresSeleccionados = [];
    /** @var array<int> */
    public array $rutasSeleccionadas = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-arrow-uturn-left';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Resúmenes';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public function mount(): void
    {
        $this->fecha = today()->toDateString();
    }

    public function getVendedores(): \Illuminate\Support\Collection
    {
        return Vendedor::where('activo', true)->whereNotNull('user_id')->orderBy('nombre')->get();
    }

    /** Cobradores que mandan reintegros (campo asignado_por, que guarda el user_id). */
    public function getCobradores(): \Illuminate\Support\Collection
    {
        return Cobrador::where('activo', true)->whereNotNull('user_id')->orderBy('nombre')->get();
    }

    public function getRutas(): \Illuminate\Support\Collection
    {
        return RutaCobro::orderBy('nombre')->get();
    }

    public function getResumen(): \Illuminate\Support\Collection
    {
        $cobradorUserIds = $this->cobradoresSeleccionados !== []
            ? Cobrador::whereIn('id', $this->cobradoresSeleccionados)->pluck('user_id')->filter()->all()
            : [];

        return ResumenReintegrosService::resumen(
            $this->fecha,
            $this->vendedoresSeleccionados,
            $cobradorUserIds,
            $this->rutasSeleccionadas,
        );
    }

    public function getTotales(\Illuminate\Support\Collection $resumen): array
    {
        return ResumenReintegrosService::totales($resumen);
    }
}
