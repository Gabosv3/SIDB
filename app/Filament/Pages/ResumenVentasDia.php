<?php

namespace App\Filament\Pages;

use App\Models\AnticipoVendedor;
use App\Models\Cliente;
use App\Models\ComisionTramo;
use App\Models\Pagare;
use App\Models\RutaCobro;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Services\AnularVentaService;
use App\Services\ResumenVentasDiaService;
use App\Services\VentaCorreccionService;
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
    /** dia|semana|mes -- controla el rango que cubre $fecha. */
    public string $periodo = 'dia';
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

    /** Fin del rango según $periodo, a partir de $fecha como ancla. */
    public function getFechaFin(): Carbon
    {
        $ancla = Carbon::parse($this->fecha);

        return match ($this->periodo) {
            'semana' => $ancla->copy()->endOfWeek(Carbon::SUNDAY),
            'mes' => $ancla->copy()->endOfMonth(),
            default => $ancla->copy(),
        };
    }

    /** Inicio del rango según $periodo, a partir de $fecha como ancla. */
    public function getFechaInicio(): Carbon
    {
        $ancla = Carbon::parse($this->fecha);

        return match ($this->periodo) {
            'semana' => $ancla->copy()->startOfWeek(Carbon::MONDAY),
            'mes' => $ancla->copy()->startOfMonth(),
            default => $ancla->copy(),
        };
    }

    public function getResumen(): \Illuminate\Support\Collection
    {
        return ResumenVentasDiaService::resumen(
            $this->getFechaInicio()->toDateString(),
            $this->vendedoresSeleccionados,
            $this->buscarCliente,
            $this->getFechaFin()->toDateString(),
        );
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
     * Cuánto debe entregar el vendedor en efectivo por ESTA venta: solo
     * aplica a ventas AL CONTADO -- lo que le corresponde a la empresa es
     * "se le recibe al vendedor" de cada producto (su ganancia es la
     * diferencia con el precio de venta), más lo que sobre de la prima si
     * se le dio de más de lo que le corresponde de comisión por esa venta.
     */
    public function entregaDeVenta(Venta $venta): float
    {
        if ($venta->tipo_pago !== 'contado') {
            return 0.0;
        }

        $porProductos = (float) $venta->detalles->sum(
            fn ($d) => $d->cantidad * (float) ($d->producto?->precio_vendedor ?? 0)
        );

        $excedentePrima = max((float) $venta->prima - $this->comisionDeVenta($venta), 0);

        return round($porProductos + $excedentePrima, 2);
    }

    /** Total a entregar hoy por cada vendedor, sumando sus ventas al contado. */
    public function getEntregaPorVendedor(\Illuminate\Support\Collection $resumen): \Illuminate\Support\Collection
    {
        return $resumen
            ->filter(fn ($r) => $r->venta->tipo_pago === 'contado' && $r->venta->vendedor)
            ->groupBy(fn ($r) => $r->venta->vendedor_id)
            ->map(function ($grupo) {
                $vendedor = $grupo->first()->venta->vendedor;

                return [
                    'vendedor' => $vendedor,
                    'total' => round($grupo->sum(fn ($r) => $this->entregaDeVenta($r->venta)), 2),
                ];
            })
            ->sortBy(fn ($g) => $g['vendedor']->nombre)
            ->values();
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
     * El tramo de comisión se aplica por cada PRODUCTO de la venta (el
     * subtotal de esa línea), no por el total de la venta -- una venta de
     * $320 con dos productos de $200 y $120 paga el tramo de $200 y el de
     * $120 por separado.
     */
    private function comisionDeVenta(Venta $venta): float
    {
        return round($venta->detalles->sum(
            fn ($d) => (float) $d->subtotal * ComisionTramo::porcentajePara((float) $d->subtotal) / 100
        ), 2);
    }

    /**
     * La prima que se le da al vendedor el mismo día de la venta no siempre
     * se la queda completa: se le tapa con lo que realmente le corresponde
     * de comisión por esa venta (según el tramo de cada producto). Si la
     * prima ofrecida es menor a eso, se queda con la prima completa igual.
     */
    public function confirmarPrimaAction(): Action
    {
        return Action::make('confirmarPrima')
            ->label('Confirmar')
            ->icon('heroicon-m-check-circle')
            ->requiresConfirmation()
            ->modalHeading('Confirmar prima del vendedor')
            ->modalDescription(function (array $arguments): string {
                $venta = Venta::with('detalles')->findOrFail($arguments['venta_id']);
                $tope = $this->comisionDeVenta($venta);
                $monto = min((float) $venta->prima, $tope);

                return sprintf(
                    'Venta: $%s (comisión por producto = $%s). Prima ofrecida: $%s. Se registrará un anticipo de $%s.',
                    number_format((float) $venta->total, 2),
                    number_format($tope, 2),
                    number_format((float) $venta->prima, 2),
                    number_format($monto, 2)
                );
            })
            ->action(function (array $arguments): void {
                $venta = Venta::with('detalles')->findOrFail($arguments['venta_id']);

                if (! $venta->vendedor_id) {
                    Notification::make()->title('Esta venta no tiene vendedor asignado')->danger()->send();

                    return;
                }

                if (AnticipoVendedor::where('venta_id', $venta->id)->exists()) {
                    Notification::make()->title('Esta venta ya tiene su prima confirmada')->warning()->send();

                    return;
                }

                $tope = $this->comisionDeVenta($venta);
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

    /**
     * Corregir una venta ya hecha (producto de más/de menos, cantidad
     * equivocada, prima con el monto incorrecto) — sin límite de tiempo,
     * a diferencia de la corrección que puede hacer el vendedor desde la
     * app (solo el mismo día y solo si todavía no tiene abonos). Bloqueada
     * igual si ya tiene abonos registrados, porque tocar los productos o la
     * prima en ese caso dejaría las cuotas ya cobradas inconsistentes.
     */
    public function corregirVentaAction(): Action
    {
        return Action::make('corregirVenta')
            ->label('Corregir')
            ->icon('heroicon-m-pencil-square')
            ->color('warning')
            ->modalHeading('Corregir venta')
            ->modalDescription('Ajusta cantidades, quita un producto de más, o corrige la prima. No se pueden agregar productos nuevos aquí — para eso, usa el formulario de edición completo en Ventas.')
            ->modalWidth('lg')
            ->fillForm(function (array $arguments): array {
                $venta = Venta::with('detalles.producto')->findOrFail($arguments['venta_id']);

                return [
                    'prima' => (float) $venta->prima,
                    'detalles' => $venta->detalles->map(fn ($d) => [
                        'producto_id'          => $d->producto_id,
                        'nombre'               => $d->producto?->nombre ?? "Producto #{$d->producto_id}",
                        'cantidad'             => $d->cantidad,
                        'precio_unitario'      => (float) $d->precio_unitario,
                        'descuento_porcentaje' => (float) $d->descuento_porcentaje,
                        'tipo_pago'            => $d->tipo_pago,
                        'cuotas'               => $d->cuotas,
                        'precio_cuota'         => $d->precio_cuota !== null ? (float) $d->precio_cuota : null,
                    ])->toArray(),
                ];
            })
            ->schema([
                Forms\Components\Repeater::make('detalles')
                    ->label('Productos')
                    ->schema([
                        Forms\Components\Hidden::make('producto_id'),
                        Forms\Components\Hidden::make('precio_unitario'),
                        Forms\Components\Hidden::make('descuento_porcentaje'),
                        Forms\Components\Hidden::make('tipo_pago'),
                        Forms\Components\Hidden::make('cuotas'),
                        Forms\Components\Hidden::make('precio_cuota'),
                        Forms\Components\TextInput::make('nombre')
                            ->label('Producto')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('cantidad')
                            ->label('Cantidad')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(3)
                    ->deletable(true)
                    ->addable(false)
                    ->reorderable(false)
                    ->required(),
                Forms\Components\TextInput::make('prima')
                    ->label('Prima inicial')
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0),
                Forms\Components\Textarea::make('motivo')
                    ->label('Motivo de la corrección')
                    ->placeholder('Ej: Se quitó una silla que sobraba, prima corregida a $10')
                    ->rows(2)
                    ->required(),
                Forms\Components\Checkbox::make('forzar')
                    ->label('Forzar corrección aunque ya tenga abonos registrados')
                    ->helperText('Los pagos ya recibidos NO se pierden, pero las cuotas se recrean desde cero -- tendrás que cuadrar a mano cuánto de lo ya cobrado corresponde a cada cuota nueva.')
                    ->default(false)
                    ->visible(function (): bool {
                        $ventaId = $this->getMountedAction()?->getArguments()['venta_id'] ?? null;

                        return auth()->user()?->hasRole('super_admin')
                            && $ventaId
                            && \App\Models\PagoVenta::where('venta_id', $ventaId)->whereNull('anulado_en')->exists();
                    }),
            ])
            ->action(function (array $data, array $arguments): void {
                $venta = Venta::with('detalles')->findOrFail($arguments['venta_id']);

                if (empty($data['detalles'])) {
                    Notification::make()->title('La venta debe tener al menos un producto')->danger()->send();

                    return;
                }

                $nuevosDetalles = collect($data['detalles'])->map(fn ($d) => [
                    'producto_id'          => $d['producto_id'],
                    'cantidad'             => (int) $d['cantidad'],
                    'precio_unitario'      => (float) $d['precio_unitario'],
                    'descuento_porcentaje' => (float) ($d['descuento_porcentaje'] ?? 0),
                    'tipo_pago'            => $d['tipo_pago'] ?? null,
                    'cuotas'               => $d['cuotas'] ?? null,
                    'precio_cuota'         => $d['precio_cuota'] ?? null,
                ])->toArray();

                // El checkbox solo se muestra a super_admin, pero se revalida
                // aquí también por si acaso -- que nadie más pueda forzarlo
                // enviando el campo a mano.
                $forzar = (bool) ($data['forzar'] ?? false) && auth()->user()?->hasRole('super_admin');

                $resultado = VentaCorreccionService::aplicarCorreccion(
                    $venta,
                    $nuevosDetalles,
                    (float) $data['prima'],
                    (float) $venta->descuento_porcentaje,
                    $venta->cliente_id,
                    null, // el panel no valida contra la asignación diaria del vendedor
                    $data['motivo'],
                    null,
                    $forzar,
                );

                if (isset($resultado['error'])) {
                    Notification::make()->title('No se pudo corregir')->body($resultado['error'])->danger()->send();

                    return;
                }

                Notification::make()->title('Venta corregida correctamente')->success()->send();
            });
    }

    /**
     * Cancela una venta sin salir de este resumen -- mismo comportamiento
     * que el botón "Cancelar" de Ventas (reintegra a la asignación diaria
     * activa si aplica, o al stock general si no).
     */
    public function cancelarVentaAction(): Action
    {
        return Action::make('cancelarVenta')
            ->label('Cancelar')
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cancelar venta')
            ->modalDescription('Se marca la venta como cancelada y se reintegra lo vendido: si el producto salió de una asignación diaria todavía activa, se le devuelve a esa asignación; si no, se reintegra al stock general.')
            ->schema([
                Forms\Components\Textarea::make('motivo')
                    ->label('Motivo de la cancelación')
                    ->required()
                    ->rows(2),
            ])
            ->action(function (array $data, array $arguments): void {
                $venta = Venta::findOrFail($arguments['venta_id']);
                $resultado = AnularVentaService::anular($venta, $data['motivo']);

                if (isset($resultado['error'])) {
                    Notification::make()->title('No se pudo cancelar')->body($resultado['error'])->danger()->send();

                    return;
                }

                Notification::make()->title('Venta cancelada')->success()->send();
            });
    }
}
