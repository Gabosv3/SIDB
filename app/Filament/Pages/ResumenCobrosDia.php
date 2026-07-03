<?php

namespace App\Filament\Pages;

use App\Models\Cobrador;
use App\Models\Venta;
use App\Services\EliminarPagoVentaService;
use App\Services\RegistrarCobroManualService;
use App\Services\ResumenCobrosDiaService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
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
    public string $buscarCliente = '';

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
        return Cobrador::where('activo', true)
            ->where('excluir_reportes', false)
            ->orderBy('nombre')
            ->get();
    }

    public function getResumen(): array
    {
        $resumen = ResumenCobrosDiaService::resumen($this->fecha, $this->cobrador_id);

        $termino = mb_strtolower(trim($this->buscarCliente));
        if ($termino === '') {
            return $resumen;
        }

        $coincide = fn (?string $nombre, ?string $codigo) => str_contains(mb_strtolower($nombre ?? ''), $termino)
            || str_contains(mb_strtolower((string) $codigo), $termino);

        return collect($resumen)
            ->map(function (array $r) use ($coincide) {
                $detalle = $r['detalle']
                    ->filter(fn ($p) => $coincide($p->cliente?->nombre_completo, $p->cliente?->codigo_anterior))
                    ->values();

                $visitasSinCobro = $r['visitas_sin_cobro']
                    ->filter(fn ($v) => $coincide($v->cliente?->nombre_completo, $v->cliente?->codigo_anterior))
                    ->values();

                $noVisitados = $r['no_visitados']
                    ->filter(fn ($c) => $coincide($c->nombre_completo, $c->codigo_anterior))
                    ->values();

                $r['detalle'] = $detalle;
                $r['visitas_sin_cobro'] = $visitasSinCobro;
                $r['no_visitados'] = $noVisitados;
                $r['total_cobrado'] = (float) $detalle->sum('monto');
                $r['total_pagos'] = $detalle->unique(fn ($p) => $p->cliente_id . '_' . $p->venta_id)->count();
                $r['clientes_visitados'] = $detalle->pluck('cliente_id')->unique()->count();
                $r['por_metodo'] = $detalle->groupBy('metodo_pago')->map(fn ($grupo) => (object) [
                    'cantidad' => $grupo->count(),
                    'monto' => (float) $grupo->sum('monto'),
                ]);

                return $r;
            })
            ->filter(fn (array $r) => $r['detalle']->isNotEmpty()
                || $r['visitas_sin_cobro']->isNotEmpty()
                || $r['no_visitados']->isNotEmpty())
            ->values()
            ->all();
    }

    public function getTotalesGenerales(array $resumen): array
    {
        return ResumenCobrosDiaService::totales($resumen);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->registrarCobroAction(),
        ];
    }

    private function puedeGestionarCobros(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function registrarCobroAction(): Action
    {
        return Action::make('registrarCobro')
            ->label('Registrar cobro')
            ->icon('heroicon-m-plus-circle')
            ->color('success')
            ->visible(fn () => $this->puedeGestionarCobros())
            ->modalHeading('Registrar cobro manual')
            ->modalDescription('El cobro se guardará con la fecha y el cobrador que indiques, y se aplicará a las cuotas pendientes más antiguas de la venta seleccionada.')
            ->modalSubmitActionLabel('Registrar cobro')
            ->schema([
                Forms\Components\Select::make('cobrador_id')
                    ->label('Cobrador')
                    ->options(fn () => $this->getCobradores()
                        ->filter(fn (Cobrador $c) => $c->user_id)
                        ->mapWithKeys(fn (Cobrador $c) => [$c->id => "{$c->nombre} {$c->apellido}"]))
                    ->default(fn () => $this->cobrador_id)
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('fecha_pago')
                    ->label('Fecha del cobro')
                    ->default(fn () => $this->fecha)
                    ->required(),

                Forms\Components\Select::make('venta_id')
                    ->label('Venta del cliente')
                    ->placeholder('Buscar por cliente o número de venta...')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Venta::where('saldo_pendiente', '>', 0)
                        ->where(fn ($q) => $q
                            ->where('numero_venta', 'like', "%{$search}%")
                            ->orWhereHas('cliente', fn ($c) => $c
                                ->where('nombre', 'like', "%{$search}%")
                                ->orWhere('apellido', 'like', "%{$search}%")
                                ->orWhere('codigo_anterior', 'like', "%{$search}%")))
                        ->with('cliente')
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn (Venta $v) => [
                            $v->id => sprintf('%s — %s (saldo $%s)', $v->numero_venta, $v->cliente?->nombre_completo ?? 'Sin cliente', number_format((float) $v->saldo_pendiente, 2)),
                        ]))
                    ->getOptionLabelUsing(function ($value) {
                        $venta = Venta::with('cliente')->find($value);
                        return $venta ? sprintf('%s — %s', $venta->numero_venta, $venta->cliente?->nombre_completo ?? 'Sin cliente') : null;
                    })
                    ->required(),

                Forms\Components\TextInput::make('monto')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0.01)
                    ->required()
                    ->rule(fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                        $venta = Venta::find($get('venta_id'));
                        if ($venta && (float) $value > (float) $venta->saldo_pendiente) {
                            $fail(sprintf('El monto supera el saldo pendiente de esta venta ($%s).', number_format((float) $venta->saldo_pendiente, 2)));
                        }
                    }),

                Forms\Components\Select::make('metodo_pago')
                    ->label('Método de pago')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'transferencia' => 'Transferencia',
                        'cheque' => 'Cheque',
                        'deposito' => 'Depósito',
                    ])
                    ->default('efectivo')
                    ->required(),

                Forms\Components\TextInput::make('referencia')
                    ->label('Referencia (opcional)')
                    ->maxLength(100),

                Forms\Components\Textarea::make('observaciones')
                    ->label('Observaciones (opcional)')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->action(function (array $data): void {
                try {
                    abort_unless($this->puedeGestionarCobros(), 403);

                    $cobrador = Cobrador::find($data['cobrador_id']);
                    if (! $cobrador?->user_id) {
                        throw new \RuntimeException('Este cobrador no tiene un usuario del sistema vinculado.');
                    }

                    $resultado = RegistrarCobroManualService::registrar([
                        'venta_id' => $data['venta_id'],
                        'monto' => $data['monto'],
                        'metodo_pago' => $data['metodo_pago'],
                        'referencia' => $data['referencia'] ?? null,
                        'observaciones' => $data['observaciones'] ?? null,
                        'fecha_pago' => $data['fecha_pago'],
                        'user_id' => $cobrador->user_id,
                    ]);

                    Notification::make()
                        ->title('Cobro registrado')
                        ->body(sprintf('Se registró $%s en %d cuota(s).', number_format($resultado['monto'], 2), $resultado['cuotas_aplicadas']))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('No se pudo registrar el cobro')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function eliminarPagoAction(): Action
    {
        return Action::make('eliminarPago')
            ->label('Eliminar pago')
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->visible(fn () => $this->puedeGestionarCobros())
            ->modalHeading('Eliminar pago registrado')
            ->modalDescription(fn (array $arguments) => sprintf(
                'Se eliminará el pago de %s por $%s. Esta acción revierte el saldo del cliente y sus cuotas, y no se puede deshacer.',
                $arguments['cliente'] ?? 'este cliente',
                number_format((float) ($arguments['monto'] ?? 0), 2)
            ))
            ->modalSubmitActionLabel('Sí, eliminar pago')
            ->schema([
                Forms\Components\Textarea::make('motivo')
                    ->label('Motivo (opcional)')
                    ->placeholder('Ej: el cobrador registró el monto duplicado')
                    ->rows(2)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Confirma tu contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword(),
            ])
            ->action(function (array $data, array $arguments): void {
                try {
                    abort_unless($this->puedeGestionarCobros(), 403);

                    $resultado = EliminarPagoVentaService::eliminar(
                        pagoVentaIds: $arguments['ids'] ?? [],
                        eliminadoPorUserId: auth()->id(),
                        motivo: $data['motivo'] ?? null,
                    );

                    Notification::make()
                        ->title('Pago eliminado')
                        ->body(sprintf('Se eliminó %d pago(s) por $%s.', $resultado['cantidad'], number_format($resultado['monto_total'], 2)))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('No se pudo eliminar el pago')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
