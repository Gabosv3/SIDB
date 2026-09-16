<?php

namespace App\Filament\Pages;

use App\Models\Cliente;
use App\Models\RutaCobro;
use App\Models\Vendedor;
use App\Services\ResumenVentasDiaService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ResumenVentasDia extends Page
{
    protected static ?string $navigationLabel = 'Ventas del Día';
    protected static ?string $title = 'Resumen de Ventas del Día';
    protected string $view = 'filament.pages.resumen-ventas-dia';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $fecha = '';
    public string $buscarCliente = '';
    /** @var array<int> */
    public array $vendedoresSeleccionados = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shopping-cart';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Resúmenes';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public function mount(): void
    {
        $this->fecha = today()->toDateString();
    }

    public function getVendedores(): \Illuminate\Support\Collection
    {
        return Vendedor::where('activo', true)->whereNotNull('user_id')->orderBy('nombre')->get();
    }

    public function getResumen(): \Illuminate\Support\Collection
    {
        return ResumenVentasDiaService::resumen($this->fecha, $this->vendedoresSeleccionados, $this->buscarCliente);
    }

    public function getTotales(\Illuminate\Support\Collection $resumen): array
    {
        return ResumenVentasDiaService::totales($resumen);
    }

    /**
     * Asignar/cambiar la ruta de cobro del cliente sin salir de este resumen
     * -- mismo efecto que hacerlo desde "Clientes por Ruta".
     */
    public function asignarRutaAction(): Action
    {
        return Action::make('asignarRuta')
            ->label('Asignar ruta')
            ->icon('heroicon-m-link')
            ->modalHeading('Asignar ruta de cobro')
            ->schema([
                Forms\Components\Select::make('ruta_cobro_id')
                    ->label('Ruta de cobro')
                    ->placeholder('Elige una ruta')
                    ->options(fn () => RutaCobro::where('activa', true)
                        ->orderBy('nombre')
                        ->get()
                        ->mapWithKeys(fn (RutaCobro $r) => [(string) $r->id => $r->nombre_con_dia]))
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $cliente = Cliente::findOrFail($arguments['cliente_id']);
                $cliente->update(['ruta_cobro_id' => $data['ruta_cobro_id']]);

                Notification::make()
                    ->title("Ruta asignada a {$cliente->nombre}")
                    ->success()
                    ->send();
            });
    }
}
