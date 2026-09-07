<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EncuestaClienteResource;
use App\Models\Cobrador;
use App\Services\ResumenEncuestasClienteService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ResumenEncuestasCliente extends Page
{
    protected static ?string $navigationLabel = 'Encuestas de Cliente';
    protected static ?string $title = 'Resumen de Encuestas de Cliente';
    protected string $view = 'filament.pages.resumen-encuestas-cliente';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $fecha = '';
    /** @var array<int> */
    public array $cobradoresSeleccionados = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Resúmenes';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public function mount(): void
    {
        $this->fecha = today()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('gestionar')
                ->label('Gestionar encuestas')
                ->icon('heroicon-m-cog-6-tooth')
                ->color('gray')
                ->url(fn () => EncuestaClienteResource::getUrl('index')),
        ];
    }

    public function getCobradores(): \Illuminate\Support\Collection
    {
        return Cobrador::where('activo', true)->orderBy('nombre')->get();
    }

    public function getResumen(): \Illuminate\Support\Collection
    {
        return ResumenEncuestasClienteService::resumen($this->fecha, $this->cobradoresSeleccionados);
    }

    public function getTotales(\Illuminate\Support\Collection $resumen): array
    {
        return ResumenEncuestasClienteService::totales($resumen);
    }
}
