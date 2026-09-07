<?php

namespace App\Filament\Pages;

use App\Filament\Resources\GarantiaResource;
use App\Models\Garantia;
use App\Models\User;
use App\Services\ResumenGarantiasService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ResumenGarantias extends Page
{
    protected static ?string $navigationLabel = 'Garantías del Día';
    protected static ?string $title = 'Resumen de Garantías del Día';
    protected string $view = 'filament.pages.resumen-garantias';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $fecha = '';
    /** @var array<int> */
    public array $asignadosSeleccionados = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Resúmenes';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public function mount(): void
    {
        $this->fecha = today()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('gestionar')
                ->label('Gestionar garantías')
                ->icon('heroicon-m-cog-6-tooth')
                ->color('gray')
                ->url(fn () => GarantiaResource::getUrl('index')),
        ];
    }

    public function getAsignados(): \Illuminate\Support\Collection
    {
        return User::whereIn('id', Garantia::whereNotNull('asignado_a')->distinct()->pluck('asignado_a'))
            ->orderBy('name')
            ->get();
    }

    public function getResumen(): \Illuminate\Support\Collection
    {
        return ResumenGarantiasService::resumen($this->fecha, $this->asignadosSeleccionados);
    }

    public function getTotales(\Illuminate\Support\Collection $resumen): array
    {
        return ResumenGarantiasService::totales($resumen);
    }
}
