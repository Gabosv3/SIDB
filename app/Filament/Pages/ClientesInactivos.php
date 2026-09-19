<?php

namespace App\Filament\Pages;

use App\Services\ClientesInactivosService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ClientesInactivos extends Page
{
    protected static ?string $navigationLabel = 'Clientes Inactivos';
    protected static ?string $title = 'Clientes sin visita ni abono';
    protected string $view = 'filament.pages.clientes-inactivos';
    protected Width|string|null $maxContentWidth = Width::Full;

    public int $dias = 30;

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

    public function getClientes(): \Illuminate\Support\Collection
    {
        return ClientesInactivosService::listar($this->dias);
    }
}
