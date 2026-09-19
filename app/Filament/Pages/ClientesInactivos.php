<?php

namespace App\Filament\Pages;

use App\Models\Cobrador;
use App\Models\RutaCobro;
use App\Services\ClientesInactivosService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ClientesInactivos extends Page
{
    protected static ?string $navigationLabel = 'Clientes Inactivos';
    protected static ?string $title = 'Clientes sin visita ni abono';
    protected string $view = 'filament.pages.clientes-inactivos';
    protected Width|string|null $maxContentWidth = Width::Full;

    public int $dias = 30;
    public ?int $ruta_id = null;
    public ?int $cobrador_id = null;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user-minus';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Reportes de Cobros';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportarPdf')
                ->label('Exportar a PDF')
                ->icon('heroicon-m-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('reporte.clientes-inactivos', [
                    'tenant'      => Filament::getTenant()?->id ?? 1,
                    'dias'        => $this->dias,
                    'ruta_id'     => $this->ruta_id,
                    'cobrador_id' => $this->cobrador_id,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getRutas(): \Illuminate\Support\Collection
    {
        return RutaCobro::where('activa', true)->orderBy('nombre')->get(['id', 'nombre']);
    }

    public function getCobradores(): \Illuminate\Support\Collection
    {
        return Cobrador::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'apellido']);
    }

    public function getClientes(): \Illuminate\Support\Collection
    {
        return ClientesInactivosService::listar($this->dias, $this->ruta_id, $this->cobrador_id);
    }
}
