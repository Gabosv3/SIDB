<?php

namespace App\Filament\Pages;

use App\Models\Cliente;
use App\Models\Pagare;
use App\Models\RutaCobro;
use App\Models\Vendedor;
use App\Models\Venta;
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

    /** IDs de cliente (dentro de este resumen) que tienen algún pagaré firmado sin enlazar a una venta. */
    public function getClientesConPagareSuelto(\Illuminate\Support\Collection $resumen): \Illuminate\Support\Collection
    {
        $clienteIds = $resumen->pluck('venta.cliente_id')->filter()->unique();

        if ($clienteIds->isEmpty()) {
            return collect();
        }

        return Pagare::whereIn('cliente_id', $clienteIds)
            ->whereNull('venta_id')
            ->pluck('cliente_id')
            ->unique();
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

    /**
     * Cuando la app sube el pagaré firmado antes de confirmar la venta y el
     * segundo paso (enlazar venta_id) no llega, el pagaré queda suelto sin
     * aparecer en este resumen -- mismo mecanismo de enlace manual que ya
     * existe en la pestaña Pagarés del cliente, pero accesible desde acá
     * directamente sobre la venta que le falta.
     */
    public function enlazarPagareAction(): Action
    {
        return Action::make('enlazarPagare')
            ->label('Enlazar pagaré')
            ->icon('heroicon-m-link')
            ->modalHeading('Enlazar pagaré a esta venta')
            ->schema([
                Forms\Components\Select::make('pagare_id')
                    ->label('Pagaré firmado (sin enlazar) de este cliente')
                    ->placeholder('Elige un pagaré')
                    ->options(function () {
                        $ventaId = $this->getMountedAction()?->getArguments()['venta_id'] ?? null;
                        $venta = Venta::findOrFail($ventaId);

                        return Pagare::where('cliente_id', $venta->cliente_id)
                            ->whereNull('venta_id')
                            ->orderByDesc('created_at')
                            ->get()
                            ->mapWithKeys(fn (Pagare $p) => [(string) $p->id => sprintf(
                                '%s — %s (financiado: $%s)',
                                $p->nombre_deudor,
                                $p->created_at->format('d/m/Y'),
                                number_format((float) $p->monto_financiado, 2)
                            )]);
                    })
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $pagare = Pagare::findOrFail($data['pagare_id']);
                $pagare->update(['venta_id' => $arguments['venta_id']]);

                Notification::make()
                    ->title('Pagaré enlazado a la venta')
                    ->success()
                    ->send();
            });
    }
}
