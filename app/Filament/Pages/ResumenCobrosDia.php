<?php

namespace App\Filament\Pages;

use App\Models\Cobrador;
use App\Models\PagoVenta;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class ResumenCobrosDia extends Page
{
    protected static ?string $navigationLabel = 'Resumen del Día';
    protected static ?string $title = 'Resumen de Cobros del Día';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.resumen-cobros-dia';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $fecha = '';
    public ?int $cobrador_id = null;

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
        return Cobrador::where('activo', true)->orderBy('nombre')->get();
    }

    public function getResumen(): array
    {
        $fecha = Carbon::parse($this->fecha);

        $cobradores = Cobrador::with('user')
            ->where('activo', true)
            ->when($this->cobrador_id, fn ($q) => $q->where('id', $this->cobrador_id))
            ->get();

        $resumen = $cobradores->map(function ($cobrador) use ($fecha) {
            $pagos = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)
                ->selectRaw('COUNT(*) AS total_pagos, COUNT(DISTINCT cliente_id) AS clientes_visitados, SUM(monto) AS total_cobrado')
                ->first();

            $porMetodo = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)
                ->selectRaw('metodo_pago, COUNT(*) AS cantidad, SUM(monto) AS monto')
                ->groupBy('metodo_pago')
                ->get()
                ->keyBy('metodo_pago');

            $detalle = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)
                ->with('cliente:id,nombre,apellido', 'venta:id,numero_venta')
                ->orderBy('created_at')
                ->get();

            return [
                'cobrador'           => $cobrador,
                'total_cobrado'      => (float) ($pagos->total_cobrado ?? 0),
                'total_pagos'        => (int) ($pagos->total_pagos ?? 0),
                'clientes_visitados' => (int) ($pagos->clientes_visitados ?? 0),
                'por_metodo'         => $porMetodo,
                'detalle'            => $detalle,
            ];
        })->filter(fn ($r) => $r['total_pagos'] > 0 || ! $this->cobrador_id);

        return $resumen->values()->all();
    }

    public function getTotalesGenerales(array $resumen): array
    {
        return [
            'total_cobrado'      => round(collect($resumen)->sum('total_cobrado'), 2),
            'total_pagos'        => collect($resumen)->sum('total_pagos'),
            'clientes_visitados' => collect($resumen)->sum('clientes_visitados'),
        ];
    }
}
