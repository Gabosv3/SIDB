<?php

namespace App\Filament\Pages;

use App\Models\AnticipoVendedor;
use App\Models\Cliente;
use App\Models\ComisionTramo;
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
use Illuminate\Support\Carbon;

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

    /** monto de anticipo ya confirmado, indexado por venta_id, para las ventas de este resumen. */
    public function getPrimasConfirmadas(\Illuminate\Support\Collection $resumen): \Illuminate\Support\Collection
    {
        $ventaIds = $resumen->pluck('venta.id')->filter()->unique();

        if ($ventaIds->isEmpty()) {
            return collect();
        }

        return AnticipoVendedor::whereIn('venta_id', $ventaIds)->pluck('monto', 'venta_id');
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

    /**
     * La prima que se le da al vendedor el mismo día de la venta no siempre
     * se la queda completa: se le tapa con lo que realmente le corresponde
     * de comisión por esa venta (según el tramo de su precio). Si la prima
     * ofrecida es menor a eso, se queda con la prima completa igual.
     */
    public function confirmarPrimaAction(): Action
    {
        return Action::make('confirmarPrima')
            ->label('Confirmar')
            ->icon('heroicon-m-check-circle')
            ->requiresConfirmation()
            ->modalHeading('Confirmar prima del vendedor')
            ->modalDescription(function (array $arguments): string {
                $venta = Venta::findOrFail($arguments['venta_id']);
                $pct = ComisionTramo::porcentajePara((float) $venta->total);
                $tope = round((float) $venta->total * $pct / 100, 2);
                $monto = min((float) $venta->prima, $tope);

                return sprintf(
                    'Venta: $%s (comisión %s%% = $%s). Prima ofrecida: $%s. Se registrará un anticipo de $%s.',
                    number_format((float) $venta->total, 2),
                    number_format($pct, 2),
                    number_format($tope, 2),
                    number_format((float) $venta->prima, 2),
                    number_format($monto, 2)
                );
            })
            ->action(function (array $arguments): void {
                $venta = Venta::findOrFail($arguments['venta_id']);

                if (! $venta->vendedor_id) {
                    Notification::make()->title('Esta venta no tiene vendedor asignado')->danger()->send();

                    return;
                }

                if (AnticipoVendedor::where('venta_id', $venta->id)->exists()) {
                    Notification::make()->title('Esta venta ya tiene su prima confirmada')->warning()->send();

                    return;
                }

                $pct = ComisionTramo::porcentajePara((float) $venta->total);
                $tope = round((float) $venta->total * $pct / 100, 2);
                $monto = min((float) $venta->prima, $tope);

                $inicioSemana = Carbon::parse($venta->fecha_venta)->startOfWeek(Carbon::MONDAY);

                AnticipoVendedor::create([
                    'vendedor_id'    => $venta->vendedor_id,
                    'autorizado_por' => auth()->id(),
                    'monto'          => $monto,
                    'fecha'          => $venta->fecha_venta->toDateString(),
                    'semana_inicio'  => $inicioSemana->toDateString(),
                    'semana_fin'     => $inicioSemana->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                    'descripcion'    => "Prima venta #{$venta->numero_venta}",
                    'estado'         => 'pendiente',
                    'venta_id'       => $venta->id,
                ]);

                Notification::make()
                    ->title("Prima confirmada: $".number_format($monto, 2))
                    ->success()
                    ->send();
            });
    }
}
